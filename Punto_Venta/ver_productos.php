<?php

$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "Productos en la base de datos:" . PHP_EOL;
$result = $pdo->query('SELECT id, nombre, codigo_barra FROM producto LIMIT 10');
foreach($result as $row) {
    echo "- ID: " . $row['id'] . ", Nombre: " . $row['nombre'] . ", Código: " . $row['codigo_barra'] . PHP_EOL;
}

echo PHP_EOL . "Stock en recibido_bodega:" . PHP_EOL;
$result = $pdo->query('SELECT rb.producto_id, p.nombre, rb.cantidad_disponible, rb.seccion_id FROM recibido_bodega rb JOIN producto p ON rb.producto_id = p.id LIMIT 10');
foreach($result as $row) {
    echo "- Producto ID: " . $row['producto_id'] . " (" . $row['nombre'] . "), Stock: " . $row['cantidad_disponible'] . ", Sección: " . $row['seccion_id'] . PHP_EOL;
}
