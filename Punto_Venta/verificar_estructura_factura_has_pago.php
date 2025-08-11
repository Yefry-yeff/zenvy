<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "Estructura de factura_has_pago:\n";
    $result = $pdo->query('DESCRIBE factura_has_pago');
    foreach($result as $row) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\nRegistros existentes en factura_has_pago:\n";
    $result = $pdo->query('SELECT * FROM factura_has_pago LIMIT 5');
    foreach($result as $row) {
        print_r($row);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
