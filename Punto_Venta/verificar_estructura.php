<?php

$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "Todas las columnas de recibido_bodega:" . PHP_EOL;
$result = $pdo->query('DESCRIBE recibido_bodega');
foreach($result as $row) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
}
