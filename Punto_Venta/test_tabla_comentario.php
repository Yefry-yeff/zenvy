<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "=== VERIFICACIÓN Y CREACIÓN DE TABLA COMENTARIO ===\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "1. Verificando estructura de tabla comentario...\n";
    try {
        $stmt = $pdo->query('DESCRIBE comentario');
        echo "✓ Tabla comentario existe\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - Columna: " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Tabla comentario no existe, creando...\n";
        $createComentario = "
            CREATE TABLE IF NOT EXISTS `comentario` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `jornada_id` INT NOT NULL,
                `tipo` ENUM('apertura', 'cierre', 'general') NOT NULL DEFAULT 'general',
                `comentario` TEXT NOT NULL,
                `usuario_id` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_jornada_id` (`jornada_id`),
                INDEX `idx_usuario_id` (`usuario_id`),
                INDEX `idx_tipo` (`tipo`)
            ) ENGINE = InnoDB;
        ";
        $pdo->exec($createComentario);
        echo "✓ Tabla comentario creada\n";
        echo "   - Campos: id, jornada_id, tipo, comentario, usuario_id, created_at, updated_at\n";
        echo "   - Tipos permitidos: apertura, cierre, general\n";
    }

    echo "\n2. Probando inserción de comentario de prueba...\n";
    
    // Verificar si existe una jornada para probar
    $stmt = $pdo->query("SELECT id FROM jornada LIMIT 1");
    $jornada = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jornada) {
        $comentarioPrueba = "Comentario de prueba para cierre de jornada - " . date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            INSERT INTO comentario (jornada_id, tipo, comentario, usuario_id, created_at, updated_at) 
            VALUES (?, 'cierre', ?, 1, NOW(), NOW())
        ");
        
        $stmt->execute([$jornada['id'], $comentarioPrueba]);
        echo "✓ Comentario de prueba insertado correctamente\n";
        echo "   - Jornada ID: {$jornada['id']}\n";
        echo "   - Tipo: cierre\n";
        echo "   - Comentario: $comentarioPrueba\n";
        
        // Verificar el comentario insertado
        $stmt = $pdo->prepare("SELECT * FROM comentario WHERE jornada_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$jornada['id']]);
        $comentarioVerificacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comentarioVerificacion) {
            echo "✓ Verificación exitosa - Comentario ID: {$comentarioVerificacion['id']}\n";
        }
    } else {
        echo "⚠️  No hay jornadas disponibles para probar\n";
        echo "   Creando jornada de prueba...\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO jornada (fecha, tienda_id, apertura, cierre, created_at, updated_at) 
            VALUES (CURDATE(), 1, 0, 1, NOW(), NOW())
        ");
        $stmt->execute();
        $jornadaId = $pdo->lastInsertId();
        
        echo "✓ Jornada de prueba creada con ID: $jornadaId\n";
    }

    echo "\n3. Verificando comentarios existentes...\n";
    $stmt = $pdo->query("
        SELECT c.*, j.fecha, u.name as usuario_nombre 
        FROM comentario c
        LEFT JOIN jornada j ON c.jornada_id = j.id
        LEFT JOIN users u ON c.usuario_id = u.id
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($comentarios) > 0) {
        echo "   Últimos " . count($comentarios) . " comentarios:\n";
        foreach ($comentarios as $comentario) {
            $fecha = $comentario['fecha'] ?? 'Sin fecha';
            $usuario = $comentario['usuario_nombre'] ?? 'Usuario desconocido';
            echo "     - ID: {$comentario['id']}, Jornada: {$comentario['jornada_id']} ($fecha)\n";
            echo "       Tipo: {$comentario['tipo']}, Usuario: $usuario\n";
            echo "       Comentario: " . substr($comentario['comentario'], 0, 50) . "...\n";
            echo "       Fecha: {$comentario['created_at']}\n\n";
        }
    } else {
        echo "   No hay comentarios registrados\n";
    }

    echo "\n✅ VERIFICACIÓN COMPLETADA\n";
    echo "La tabla comentario está lista para el sistema de cierre de jornada\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA VERIFICACIÓN ===\n";
?>
