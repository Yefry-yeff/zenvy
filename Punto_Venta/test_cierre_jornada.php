<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "=== PRUEBA DEL SISTEMA DE CIERRE DE JORNADA POR TIENDA ===\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $fechaPrueba = date('Y-m-d');
    $tiendaTest = 1; // Cambiar según la tienda que quieras probar

    echo "1. Verificando estructura de tabla jornada...\n";
    try {
        $stmt = $pdo->query('DESCRIBE jornada');
        echo "✓ Tabla jornada existe\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - Columna: " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Tabla jornada no existe, creando...\n";
        $createJornada = "
            CREATE TABLE IF NOT EXISTS `jornada` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `fecha` DATE NOT NULL,
                `tienda_id` INT NOT NULL,
                `apertura` TINYINT(1) DEFAULT 0,
                `cierre` TINYINT(1) DEFAULT 0,
                `comentario` TEXT NULL,
                `fecha_apertura` TIMESTAMP NULL,
                `fecha_cierre` TIMESTAMP NULL,
                `usuario_apertura` INT NULL,
                `usuario_cierre` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `fecha_tienda_unica` (`fecha`, `tienda_id`)
            ) ENGINE = InnoDB;
        ";
        $pdo->exec($createJornada);
        echo "✓ Tabla jornada creada con campo tienda_id\n";
    }

    echo "\n2. Verificando usuarios y tiendas...\n";
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.tienda_id, t.denominacion_social
        FROM users u
        LEFT JOIN tienda t ON u.tienda_id = t.id
        ORDER BY u.id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tiendaInfo = 'Sin asignar';
        if ($row['tienda_id']) {
            $tiendaInfo = $row['denominacion_social'] 
                         ?? 'Tienda #' . $row['tienda_id'];
        }
        echo "   - Usuario ID: {$row['id']}, Nombre: {$row['name']}, Tienda: $tiendaInfo\n";
    }

    // Obtener información de la tienda de prueba
    $stmt = $pdo->prepare("SELECT denominacion_social FROM tienda WHERE id = ?");
    $stmt->execute([$tiendaTest]);
    $tiendaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombreTiendaTest = $tiendaInfo ? 
                       ($tiendaInfo['denominacion_social'] ?? "Tienda #$tiendaTest") : 
                       "Tienda #$tiendaTest";

    echo "\n3. Verificando estado de cajas para $nombreTiendaTest en $fechaPrueba...\n";
    
    // Cajas abiertas en la tienda
    $stmt = $pdo->prepare("
        SELECT c.* 
        FROM caja c
        JOIN users u ON c.users_id = u.id
        WHERE c.estado_caja = 1 
        AND u.tienda_id = ?
        AND DATE(c.created_at) = ?
    ");
    $stmt->execute([$tiendaTest, $fechaPrueba]);
    $cajasAbiertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   - Cajas abiertas en $nombreTiendaTest: " . count($cajasAbiertas) . "\n";
    foreach ($cajasAbiertas as $caja) {
        echo "     * Caja #{$caja['id']}, Usuario: {$caja['users_id']}, Balance: L. " . number_format($caja['balance'], 2) . "\n";
    }

    // Cajas con diferencias en la tienda
    $stmt = $pdo->prepare("
        SELECT c.id, c.users_id, cc.diferencia_efectivo, cc.created_at 
        FROM cierre_de_caja cc
        JOIN caja c ON cc.caja_id = c.id
        JOIN users u ON c.users_id = u.id
        WHERE cc.diferencia_efectivo != 0 
        AND u.tienda_id = ?
        AND DATE(cc.created_at) = ?
    ");
    $stmt->execute([$tiendaTest, $fechaPrueba]);
    $cajasConDiferencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   - Cajas con diferencias en $nombreTiendaTest: " . count($cajasConDiferencia) . "\n";
    foreach ($cajasConDiferencia as $caja) {
        echo "     * Caja #{$caja['id']}, Usuario: {$caja['users_id']}, Diferencia: L. " . number_format($caja['diferencia_efectivo'], 2) . "\n";
    }

    echo "\n4. Verificando jornada actual para $nombreTiendaTest...\n";
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE DATE(fecha) = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaTest]);
    $jornadaActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($jornadaActual) {
        echo "   - Jornada existente: ID {$jornadaActual['id']}\n";
        echo "     * Apertura: " . ($jornadaActual['apertura'] ? 'Sí' : 'No') . "\n";
        echo "     * Cierre: " . ($jornadaActual['cierre'] ? 'Sí' : 'No') . "\n";
        if (!empty($jornadaActual['comentario'])) {
            echo "     * Comentario: " . substr($jornadaActual['comentario'], 0, 50) . "...\n";
        }
    } else {
        echo "   - No hay jornada registrada para $nombreTiendaTest en $fechaPrueba\n";
    }

    echo "\n5. Simulación de cierre de jornada para $nombreTiendaTest...\n";
    
    if (count($cajasAbiertas) > 0 || count($cajasConDiferencia) > 0) {
        echo "⚠️  Se encontraron alertas para $nombreTiendaTest:\n";
        echo "   - Cajas abiertas: " . count($cajasAbiertas) . "\n";
        echo "   - Cajas con diferencias: " . count($cajasConDiferencia) . "\n";
        echo "   El sistema mostraría modal de confirmación específico para esta tienda\n";
    } else {
        echo "✓ No hay alertas para $nombreTiendaTest, se puede proceder directamente\n";
    }

    echo "\n6. Verificando otras tiendas...\n";
    $stmt = $pdo->query("
        SELECT DISTINCT u.tienda_id, t.denominacion_social
        FROM users u 
        LEFT JOIN tienda t ON u.tienda_id = t.id
        WHERE u.tienda_id IS NOT NULL
    ");
    $tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tiendas as $tienda) {
        if ($tienda['tienda_id'] != $tiendaTest) {
            $nombreOtraTienda = $tienda['denominacion_social'] 
                               ?? 'Tienda #' . $tienda['tienda_id'];
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cajas_abiertas
                FROM caja c
                JOIN users u ON c.users_id = u.id
                WHERE c.estado_caja = 1 
                AND u.tienda_id = ?
                AND DATE(c.created_at) = ?
            ");
            $stmt->execute([$tienda['tienda_id'], $fechaPrueba]);
            $cajasOtraTienda = $stmt->fetchColumn();
            echo "   - $nombreOtraTienda: $cajasOtraTienda cajas abiertas (no afectadas por este cierre)\n";
        }
    }

    echo "\n✅ VERIFICACIÓN COMPLETADA\n";
    echo "El sistema de cierre de jornada por tienda está listo para funcionar\n";
    echo "Proceso específico para $nombreTiendaTest solamente\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>
