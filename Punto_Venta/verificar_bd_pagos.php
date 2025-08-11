<?php

$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo 'Últimas facturas:' . PHP_EOL;
$stmt = $pdo->query('SELECT id, numero_factura, total, fecha_emision FROM factura ORDER BY id DESC LIMIT 5');
while($row = $stmt->fetch()) {
    echo 'ID: ' . $row['id'] . ', Número: ' . $row['numero_factura'] . ', Total: ' . $row['total'] . ', Fecha: ' . $row['fecha_emision'] . PHP_EOL;
}

echo PHP_EOL . 'Últimos pagos registrados:' . PHP_EOL;
$stmt = $pdo->query('
    SELECT fhp.*, tp.nombre as tipo_pago_nombre, f.numero_factura
    FROM factura_has_pago fhp
    JOIN tipo_pago tp ON fhp.tipo_pago_id = tp.id
    JOIN factura f ON fhp.factura_id = f.id
    ORDER BY fhp.factura_id DESC
    LIMIT 10
');
while($row = $stmt->fetch()) {
    echo 'Factura: ' . $row['numero_factura'] . ', Tipo: ' . $row['tipo_pago_nombre'] . ', Total Factura: ' . $row['total_factura'] . ', Pago Recibido: ' . $row['pago_recibido'] . ', Cambio: ' . $row['cambio'] . PHP_EOL;
}

echo PHP_EOL . 'Tipos de pago disponibles:' . PHP_EOL;
$stmt = $pdo->query('SELECT * FROM tipo_pago ORDER BY id');
while($row = $stmt->fetch()) {
    echo 'ID: ' . $row['id'] . ', Nombre: ' . $row['nombre'] . PHP_EOL;
}
