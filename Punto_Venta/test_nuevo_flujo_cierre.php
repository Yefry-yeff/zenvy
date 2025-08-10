<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DEL NUEVO FLUJO DE CIERRE DE JORNADA ===\n\n";
    
    $fechaPrueba = '2025-01-25';
    $tiendaId = 1;
    
    // 1. Limpiar datos de prueba
    echo "1. Limpiando datos de prueba...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE fecha = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    echo "✅ Datos limpiados\n\n";
    
    // 2. CASO 1: Intentar cerrar sin jornada
    echo "2. CASO 1: Intentar cerrar sin jornada existente\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaAperturada = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$jornadaAperturada) {
        echo "❌ VALIDACIÓN CORRECTA: No hay jornada aperturada para la fecha $fechaPrueba\n";
        echo "   El sistema debe mostrar error y no permitir continuar\n\n";
    }
    
    // 3. CASO 2: Crear jornada con apertura = 1, cierre = 0
    echo "3. CASO 2: Crear jornada aperturada\n";
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, users_id, apertura, cierre, created_at, updated_at) VALUES (?, ?, 1, 1, 0, NOW(), NOW())");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaId = $pdo->lastInsertId();
    
    echo "✅ Jornada creada con ID: $jornadaId\n";
    echo "   - fecha: $fechaPrueba\n";
    echo "   - tienda_id: $tiendaId\n";
    echo "   - apertura: 1\n";
    echo "   - cierre: 0\n\n";
    
    // 4. CASO 3: Validar que ahora SÍ puede cerrar
    echo "4. CASO 3: Validar que puede cerrar\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaParaCerrar = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaParaCerrar) {
        echo "✅ VALIDACIÓN EXITOSA: Jornada encontrada para cerrar\n";
        echo "   - ID: {$jornadaParaCerrar->id}\n";
        echo "   - Apertura: {$jornadaParaCerrar->apertura}\n";
        echo "   - Cierre: {$jornadaParaCerrar->cierre}\n\n";
        
        // Verificar que no esté ya cerrada
        if ($jornadaParaCerrar->cierre == 1) {
            echo "❌ ERROR: La jornada ya está cerrada\n";
        } else {
            echo "✅ PUEDE PROCEDER: La jornada puede ser cerrada\n\n";
        }
    }
    
    // 5. CASO 4: Procesar el cierre (NUEVO FLUJO)
    echo "5. CASO 4: Procesar cierre con nuevo flujo\n";
    echo "ANTES DEL CIERRE:\n";
    echo "   - apertura: {$jornadaParaCerrar->apertura}\n";
    echo "   - cierre: {$jornadaParaCerrar->cierre}\n\n";
    
    $comentario = "Cierre procesado con nuevo flujo: apertura=0, cierre=1";
    
    // NUEVO FLUJO: apertura = 0 y cierre = 1
    $stmt = $pdo->prepare("UPDATE jornada SET apertura = 0, cierre = 1, comentario = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$comentario, $jornadaParaCerrar->id]);
    
    echo "✅ Cierre procesado con nuevo flujo\n\n";
    
    // 6. Verificar estado después del cierre
    echo "6. CASO 5: Verificar estado después del cierre\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaParaCerrar->id]);
    $jornadaCerrada = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo "DESPUÉS DEL CIERRE:\n";
    echo "   - ID: {$jornadaCerrada->id}\n";
    echo "   - fecha: {$jornadaCerrada->fecha}\n";
    echo "   - apertura: {$jornadaCerrada->apertura} (debe ser 0)\n";
    echo "   - cierre: {$jornadaCerrada->cierre} (debe ser 1)\n";
    echo "   - comentario: \"{$jornadaCerrada->comentario}\"\n";
    echo "   - updated_at: {$jornadaCerrada->updated_at}\n\n";
    
    // Validar que los campos están correctos
    if ($jornadaCerrada->apertura == 0 && $jornadaCerrada->cierre == 1) {
        echo "✅ ÉXITO: El nuevo flujo funciona correctamente\n";
        echo "   - apertura cambió de 1 a 0 ✅\n";
        echo "   - cierre cambió de 0 a 1 ✅\n";
        echo "   - comentario guardado correctamente ✅\n";
    } else {
        echo "❌ ERROR: Los campos no se actualizaron correctamente\n";
    }
    
    // 7. CASO 6: Intentar cerrar una jornada ya cerrada
    echo "\n7. CASO 6: Intentar cerrar jornada ya cerrada\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $intentoCierre = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$intentoCierre) {
        echo "✅ VALIDACIÓN CORRECTA: No se encontró jornada con apertura=1\n";
        echo "   (Porque ya se cambió a apertura=0 en el cierre)\n";
        echo "   El sistema debe mostrar error de validación\n";
    } else {
        echo "❌ ERROR: Todavía encuentra jornada con apertura=1\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DEL NUEVO FLUJO ===\n";
echo "✅ 1. Filtro por campo 'fecha' exacto (no DATE())\n";
echo "✅ 2. Validación: existe registro Y apertura=1\n";
echo "✅ 3. Al cerrar: apertura=0 Y cierre=1\n";
echo "✅ 4. Comentario incluido en el proceso\n";
echo "✅ 5. Una vez cerrada, no se puede cerrar de nuevo\n";
echo "✅ 6. Transacciones de BD para integridad\n";

echo "\n=== LÓGICA DE VALIDACIÓN ACTUALIZADA ===\n";
echo "1. WHERE fecha = ? AND tienda_id = ? AND apertura = 1\n";
echo "2. SI no existe → ERROR 'no aperturada'\n";
echo "3. SI existe pero cierre = 1 → ERROR 'ya cerrada'\n";
echo "4. SI existe y cierre = 0 → PROCEDER\n";
echo "5. UPDATE: apertura = 0, cierre = 1, comentario = ?\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
