<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo "=== ESTRUCTURA DE LA TABLA FACTURA ===\n";
    $result = $pdo->query('DESCRIBE factura');
    
    $campoEncontrado = false;
    foreach($result as $row) {
        echo "{$row['Field']} - {$row['Type']} - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL');
        if ($row['Default'] !== null) {
            echo " - Default: {$row['Default']}";
        }
        echo "\n";
        
        if ($row['Field'] === 'factura_imagen') {
            $campoEncontrado = true;
            echo "✓ Campo factura_imagen encontrado!\n";
        }
    }
    
    if (!$campoEncontrado) {
        echo "\n❌ Campo factura_imagen NO encontrado en la tabla\n";
    } else {
        echo "\n✅ Campo factura_imagen agregado correctamente\n";
    }
    
    // Verificar si hay facturas existentes
    echo "\n=== FACTURAS EXISTENTES (últimas 3) ===\n";
    $result = $pdo->query('SELECT id, numero_factura, total, factura_imagen FROM factura ORDER BY id DESC LIMIT 3');
    
    foreach($result as $row) {
        $imagenStatus = $row['factura_imagen'] ? 'Tiene imagen (' . strlen($row['factura_imagen']) . ' bytes)' : 'Sin imagen';
        echo "ID: {$row['id']}, Factura: {$row['numero_factura']}, Total: L. {$row['total']}, Imagen: $imagenStatus\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
