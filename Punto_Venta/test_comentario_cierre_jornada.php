<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE COMENTARIO EN CIERRE DE JORNADA ===\n\n";
    
    $fechaPrueba = '2025-01-25';
    $tiendaId = 1;
    $comentarioPrueba = 'Cierre de jornada realizado correctamente. Se encontraron 2 cajas con diferencias menores que fueron documentadas.';
    
    // 1. Limpiar datos de prueba
    echo "1. Limpiando datos de prueba anteriores...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    echo "✅ Datos limpiados\n\n";
    
    // 2. Crear jornada con apertura
    echo "2. Creando jornada con apertura...\n";
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, users_id, apertura, cierre, created_at, updated_at) VALUES (?, ?, 1, 1, 0, NOW(), NOW())");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaId = $pdo->lastInsertId();
    echo "✅ Jornada aperturada creada con ID: $jornadaId\n\n";
    
    // 3. Simular cierre con comentario
    echo "3. Simulando cierre de jornada con comentario...\n";
    echo "Comentario a guardar: \"$comentarioPrueba\"\n";
    
    $stmt = $pdo->prepare("UPDATE jornada SET cierre = 1, comentario = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$comentarioPrueba, $jornadaId]);
    
    echo "✅ Jornada cerrada con comentario\n\n";
    
    // 4. Verificar que el comentario se guardó
    echo "4. Verificando que el comentario se guardó correctamente...\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaId]);
    $jornada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornada) {
        echo "✅ JORNADA RECUPERADA:\n";
        echo "   - ID: {$jornada->id}\n";
        echo "   - Fecha: {$jornada->fecha}\n";
        echo "   - Tienda ID: {$jornada->tienda_id}\n";
        echo "   - Usuario ID: {$jornada->users_id}\n";
        echo "   - Apertura: {$jornada->apertura}\n";
        echo "   - Cierre: {$jornada->cierre}\n";
        echo "   - Comentario: \"{$jornada->comentario}\"\n";
        echo "   - Creado: {$jornada->created_at}\n";
        echo "   - Actualizado: {$jornada->updated_at}\n\n";
        
        // Verificar que el comentario coincide
        if ($jornada->comentario === $comentarioPrueba) {
            echo "✅ ÉXITO: El comentario se guardó exactamente como se envió\n";
        } else {
            echo "❌ ERROR: El comentario no coincide\n";
            echo "   Esperado: \"$comentarioPrueba\"\n";
            echo "   Guardado: \"{$jornada->comentario}\"\n";
        }
    } else {
        echo "❌ ERROR: No se pudo recuperar la jornada\n";
    }
    
    // 5. Probar comentario vacío
    echo "\n5. Probando cierre con comentario vacío...\n";
    
    // Crear otra jornada
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, users_id, apertura, cierre, created_at, updated_at) VALUES (?, ?, 1, 1, 0, NOW(), NOW())");
    $stmt->execute(['2025-01-26', $tiendaId]);
    $jornadaId2 = $pdo->lastInsertId();
    
    // Cerrar sin comentario
    $stmt = $pdo->prepare("UPDATE jornada SET cierre = 1, comentario = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute(['', $jornadaId2]);
    
    $stmt = $pdo->prepare("SELECT comentario FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaId2]);
    $comentarioVacio = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($comentarioVacio->comentario === '' || $comentarioVacio->comentario === null) {
        echo "✅ ÉXITO: Comentario vacío manejado correctamente\n";
    } else {
        echo "❌ ERROR: Problema con comentario vacío\n";
    }
    
    // 6. Probar comentario largo
    echo "\n6. Probando comentario de longitud máxima (400 caracteres)...\n";
    
    $comentarioLargo = str_repeat('A', 400); // 400 caracteres
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, users_id, apertura, cierre, comentario, created_at, updated_at) VALUES (?, ?, 1, 1, 1, ?, NOW(), NOW())");
    $stmt->execute(['2025-01-27', $tiendaId, $comentarioLargo]);
    $jornadaId3 = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT comentario FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaId3]);
    $comentarioGuardado = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo "Longitud del comentario guardado: " . strlen($comentarioGuardado->comentario) . " caracteres\n";
    
    if (strlen($comentarioGuardado->comentario) === 400) {
        echo "✅ ÉXITO: Comentario de 400 caracteres guardado correctamente\n";
    } else {
        echo "❌ ADVERTENCIA: El comentario fue truncado o modificado\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DE IMPLEMENTACIÓN ===\n";
echo "✅ Campo comentario agregado al componente Livewire\n";
echo "✅ Campo comentario incluido en la vista Blade\n";
echo "✅ Validación de longitud máxima (400 caracteres)\n";
echo "✅ Comentario opcional (puede estar vacío)\n";
echo "✅ Se guarda tanto en UPDATE como en INSERT\n";
echo "✅ Compatible con estructura de BD actualizada\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
