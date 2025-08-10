<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "=== PRUEBA DE ESTRUCTURA JORNADA CON COMENTARIO ===\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "1. Verificando estructura de tabla jornada...\n";
    $stmt = $pdo->query('DESCRIBE jornada');
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tieneComentario = false;
    foreach ($columnas as $columna) {
        echo "   - Columna: " . $columna['Field'] . " (" . $columna['Type'] . ")\n";
        if ($columna['Field'] === 'comentario') {
            $tieneComentario = true;
        }
    }

    if ($tieneComentario) {
        echo "✓ La tabla jornada tiene la columna comentario\n";
    } else {
        echo "⚠️  La tabla jornada NO tiene la columna comentario\n";
        echo "   Agregando columna comentario...\n";
        
        $pdo->exec("ALTER TABLE jornada ADD COLUMN comentario TEXT NULL AFTER cierre");
        echo "✓ Columna comentario agregada a la tabla jornada\n";
    }

    echo "\n2. Probando inserción de jornada con comentario...\n";
    
    $fechaPrueba = date('Y-m-d');
    $tiendaPrueba = 1;
    $comentarioPrueba = "Comentario de prueba para cierre - " . date('H:i:s');
    
    // Verificar si ya existe una jornada para hoy
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaPrueba]);
    $jornadaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jornadaExistente) {
        echo "   Actualizando jornada existente ID: {$jornadaExistente['id']}\n";
        $stmt = $pdo->prepare("UPDATE jornada SET comentario = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$comentarioPrueba, $jornadaExistente['id']]);
        $jornadaId = $jornadaExistente['id'];
    } else {
        echo "   Creando nueva jornada...\n";
        $stmt = $pdo->prepare("
            INSERT INTO jornada (fecha, tienda_id, apertura, cierre, comentario, created_at, updated_at) 
            VALUES (?, ?, 0, 1, ?, NOW(), NOW())
        ");
        $stmt->execute([$fechaPrueba, $tiendaPrueba, $comentarioPrueba]);
        $jornadaId = $pdo->lastInsertId();
    }
    
    echo "✓ Jornada procesada - ID: $jornadaId\n";
    
    // Verificar el comentario guardado
    $stmt = $pdo->prepare("SELECT fecha, tienda_id, comentario, created_at FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaId]);
    $jornada = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n3. Verificando comentario guardado...\n";
    echo "   - Fecha: {$jornada['fecha']}\n";
    echo "   - Tienda ID: {$jornada['tienda_id']}\n";
    echo "   - Comentario: {$jornada['comentario']}\n";
    echo "   - Creado: {$jornada['created_at']}\n";

    echo "\n4. Mostrando últimas jornadas con comentarios...\n";
    $stmt = $pdo->query("
        SELECT j.id, j.fecha, j.tienda_id, j.comentario, t.denominacion_social
        FROM jornada j
        LEFT JOIN tienda t ON j.tienda_id = t.id
        WHERE j.comentario IS NOT NULL AND j.comentario != ''
        ORDER BY j.created_at DESC
        LIMIT 5
    ");
    
    $jornadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($jornadas) > 0) {
        foreach ($jornadas as $jornada) {
            $tienda = $jornada['denominacion_social'] ?? 'Tienda #' . $jornada['tienda_id'];
            echo "   - ID: {$jornada['id']}, Fecha: {$jornada['fecha']}, Tienda: $tienda\n";
            echo "     Comentario: " . substr($jornada['comentario'], 0, 60) . "...\n\n";
        }
    } else {
        echo "   No hay jornadas con comentarios\n";
    }

    echo "\n✅ VERIFICACIÓN COMPLETADA\n";
    echo "La tabla jornada está lista con la columna comentario\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";
?>
