<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "=== Columnas de recibido_bodega ===" . PHP_EOL;
$result = $pdo->query('DESCRIBE recibido_bodega');
foreach($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}

echo PHP_EOL . "=== Datos de ejemplo con código de barra 7421206000039 ===" . PHP_EOL;
$stmt = $pdo->prepare('
    SELECT rb.*, s.producto_id, seg.bodega_id, p.codigo_barra, b.nombre as bodega_nombre, b.principal
    FROM recibido_bodega rb
    JOIN seccion s ON rb.seccion_id = s.id  
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    JOIN producto p ON s.producto_id = p.id
    WHERE p.codigo_barra = ?
');
$stmt->execute(['7421206000039']);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "No se encontraron registros para el código de barra 7421206000039" . PHP_EOL;
} else {
    foreach($rows as $row) {
        echo "ID: {$row['id']}, Cantidad: {$row['cantidad']}, Producto ID: {$row['producto_id']}, Bodega: {$row['bodega_nombre']}, Principal: {$row['principal']}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Test de query actual en Factura.php ===" . PHP_EOL;
$stmt = $pdo->prepare('
    SELECT SUM(rb.cantidad_disponible) as stock_total
    FROM recibido_bodega rb
    JOIN seccion s ON rb.seccion_id = s.id
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    WHERE b.tienda_id = 1
    AND b.principal = 1
    AND b.estado_id = 1
    AND rb.producto_id = (SELECT id FROM producto WHERE codigo_barra = ?)
    AND rb.estado_id = 1
');
$stmt->execute(['7421206000039']);
$result = $stmt->fetch();
echo "Stock con query actual: " . ($result['stock_total'] ?? 'NULL') . PHP_EOL;

echo PHP_EOL . "=== Test de query corregida ===" . PHP_EOL;
$stmt = $pdo->prepare('
    SELECT SUM(rb.cantidad) as stock_total
    FROM recibido_bodega rb
    JOIN seccion s ON rb.seccion_id = s.id
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    WHERE b.tienda_id = 1
    AND b.principal = 1
    AND b.estado_id = 1
    AND s.producto_id = (SELECT id FROM producto WHERE codigo_barra = ?)
    AND rb.estado_id = 1
');
$stmt->execute(['7421206000039']);
$result = $stmt->fetch();
echo "Stock con query corregida: " . ($result['stock_total'] ?? 'NULL') . PHP_EOL;
