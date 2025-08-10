<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=punto_venta', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== BUSCAR TABLAS CON 'PRODUCTO' ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%producto%'");
    $tables = $stmt->fetchAll();
    
    foreach($tables as $table) {
        echo "Tabla encontrada: " . array_values($table)[0] . "\n";
    }
    
    echo "\n=== BUSCAR TABLA PRINCIPAL DE PRODUCTOS ===\n";
    $possibleTables = ['producto', 'productos', 'articulo', 'articulos', 'item', 'items'];
    
    foreach($possibleTables as $tableName) {
        try {
            $stmt = $pdo->query("DESCRIBE $tableName");
            echo "✓ Tabla '$tableName' existe\n";
            
            // Mostrar algunos campos
            $columns = $stmt->fetchAll();
            foreach($columns as $column) {
                if(stripos($column['Field'], 'nombre') !== false || 
                   stripos($column['Field'], 'descuento') !== false ||
                   stripos($column['Field'], 'edad') !== false) {
                    echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
                }
            }
            break;
        } catch(Exception $e) {
            echo "✗ Tabla '$tableName' no existe\n";
        }
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
