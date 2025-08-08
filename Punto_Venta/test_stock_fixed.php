<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo "=== Columnas de seccion ===" . PHP_EOL;
$result = $pdo->query('DESCRIBE seccion');
foreach($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}

echo PHP_EOL . "=== Datos de ejemplo con código de barra 7421206000039 ===" . PHP_EOL;
$stmt = $pdo->prepare('
    SELECT rb.*, p.codigo_barra, b.nombre as bodega_nombre, b.principal, b.tienda_id
    FROM recibido_bodega rb
    JOIN producto p ON rb.producto_id = p.id
    JOIN seccion s ON rb.seccion_id = s.id  
    JOIN segmento seg ON s.segmento_id = seg.id
    JOIN bodega b ON seg.bodega_id = b.id
    WHERE p.codigo_barra = ?
');
$stmt->execute(['7421206000039']);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "No se encontraron registros para el código de barra 7421206000039" . PHP_EOL;
} else {
    foreach($rows as $row) {
        echo "ID: {$row['id']}, Cantidad Disponible: {$row['cantidad_disponible']}, Producto ID: {$row['producto_id']}, Bodega: {$row['bodega_nombre']}, Principal: {$row['principal']}, Tienda: {$row['tienda_id']}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Test de query corregida ===" . PHP_EOL;
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
echo "Stock con query corregida: " . ($result['stock_total'] ?? 'NULL') . PHP_EOL;
?>
