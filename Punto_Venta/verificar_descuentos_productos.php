<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=db_zenvy', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== CAMPOS DE DESCUENTO EN TABLA PRODUCTO ===\n";
    $stmt = $pdo->query('DESCRIBE producto');
    $columns = $stmt->fetchAll();
    
    foreach($columns as $column) {
        if(stripos($column['Field'], 'descuento') !== false) {
            echo "Campo: " . $column['Field'] . " (" . $column['Type'] . ") - Nulo: " . $column['Null'] . " - Default: " . ($column['Default'] ?? 'NULL') . "\n";
        }
    }
    
    echo "\n=== PRODUCTOS Y SUS CONFIGURACIONES DE DESCUENTO ===\n";
    $stmt = $pdo->query('SELECT id, nombre, descuento_tercera, descuento_cuarta FROM producto ORDER BY id LIMIT 10');
    $productos = $stmt->fetchAll();
    
    foreach($productos as $producto) {
        echo "ID: " . $producto['id'] . " - " . $producto['nombre'] . "\n";
        echo "  Descuento 3ra edad: " . ($producto['descuento_tercera'] ?? 'NULL') . "\n";
        echo "  Descuento 4ta edad: " . ($producto['descuento_cuarta'] ?? 'NULL') . "\n";
        echo "  Permite 3ra edad: " . (($producto['descuento_tercera'] ?? 0) > 0 ? 'SÍ' : 'NO') . "\n";
        echo "  Permite 4ta edad: " . (($producto['descuento_cuarta'] ?? 0) > 0 ? 'SÍ' : 'NO') . "\n";
        echo "  ----------\n";
    }
    
    echo "\n=== ESTADÍSTICAS DE DESCUENTOS ===\n";
    $stmt = $pdo->query('SELECT 
        COUNT(*) as total_productos,
        COUNT(CASE WHEN descuento_tercera > 0 THEN 1 END) as con_descuento_3ra,
        COUNT(CASE WHEN descuento_cuarta > 0 THEN 1 END) as con_descuento_4ta,
        COUNT(CASE WHEN descuento_tercera > 0 OR descuento_cuarta > 0 THEN 1 END) as con_algun_descuento
        FROM producto WHERE estado_id = 1');
    $stats = $stmt->fetch();
    
    echo "Total productos activos: " . $stats['total_productos'] . "\n";
    echo "Con descuento 3ra edad: " . $stats['con_descuento_3ra'] . "\n";
    echo "Con descuento 4ta edad: " . $stats['con_descuento_4ta'] . "\n";
    echo "Con algún descuento por edad: " . $stats['con_algun_descuento'] . "\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
