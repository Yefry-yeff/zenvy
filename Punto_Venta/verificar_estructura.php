<?php

$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "Estructura de factura_has_pago:" . PHP_EOL;
$result = $pdo->query('DESCRIBE factura_has_pago');
foreach($result as $row) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
}
