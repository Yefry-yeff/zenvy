<?php

$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "Verificando relación seccion -> segmento -> bodega:" . PHP_EOL;

$query = "
SELECT
    s.id as seccion_id,
    s.descripcion as seccion_nombre,
    seg.id as segmento_id,
    seg.descripcion as segmento_nombre,
    b.id as bodega_id,
    b.nombre as bodega_nombre,
    b.principal,
    b.tienda_id
FROM seccion s
JOIN segmento seg ON s.segmento_id = seg.id
JOIN bodega b ON seg.bodega_id = b.id
LIMIT 10
";

$result = $pdo->query($query);
foreach($result as $row) {
    echo "- Sección {$row['seccion_id']} ({$row['seccion_nombre']}) -> Segmento {$row['segmento_id']} -> Bodega {$row['bodega_id']} ({$row['bodega_nombre']}) - Principal: " . ($row['principal'] ? 'SÍ' : 'NO') . " - Tienda: {$row['tienda_id']}" . PHP_EOL;
}

echo PHP_EOL . "Stock por bodega principal:" . PHP_EOL;

$query2 = "
SELECT
    p.id as producto_id,
    p.nombre as producto_nombre,
    b.id as bodega_id,
    b.nombre as bodega_nombre,
    b.tienda_id,
    SUM(rb.cantidad_disponible) as total_stock
FROM recibido_bodega rb
JOIN producto p ON rb.producto_id = p.id
JOIN seccion s ON rb.seccion_id = s.id
JOIN segmento seg ON s.segmento_id = seg.id
JOIN bodega b ON seg.bodega_id = b.id
WHERE b.principal = 1
GROUP BY p.id, b.id
LIMIT 10
";

$result = $pdo->query($query2);
foreach($result as $row) {
    echo "- Producto {$row['producto_id']} ({$row['producto_nombre']}) en Bodega Principal {$row['bodega_id']} ({$row['bodega_nombre']}) - Tienda {$row['tienda_id']}: {$row['total_stock']} unidades" . PHP_EOL;
}
