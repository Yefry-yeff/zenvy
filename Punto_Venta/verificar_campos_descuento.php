<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=punto_venta', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== ESTRUCTURA DE TABLA PRODUCTO ===\n";
    $stmt = $pdo->query('DESCRIBE producto');
    $columns = $stmt->fetchAll();
    
    foreach($columns as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . "\n";
    }
    
    echo "\n=== CAMPOS RELACIONADOS CON DESCUENTOS ===\n";
    foreach($columns as $column) {
        if(stripos($column['Field'], 'descuento') !== false || 
           stripos($column['Field'], 'edad') !== false ||
           stripos($column['Field'], 'tercera') !== false ||
           stripos($column['Field'], 'cuarta') !== false) {
            echo "Campo encontrado: " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    echo "\n=== EJEMPLO DE PRODUCTOS CON SUS CAMPOS ===\n";
    $stmt = $pdo->query('SELECT id, nombre FROM producto LIMIT 5');
    $productos = $stmt->fetchAll();
    
    foreach($productos as $producto) {
        echo "ID: " . $producto['id'] . " - " . $producto['nombre'] . "\n";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
