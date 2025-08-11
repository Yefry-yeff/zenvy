<?php
$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

echo 'Verificando tablas de facturación en db_zenvy:' . PHP_EOL;
$tablas = ['factura', 'factura_has_producto', 'factura_has_pago', 'tipo_pago'];

foreach($tablas as $tabla) {
    try {
        $result = $pdo->query('DESCRIBE ' . $tabla);
        echo '✓ Tabla ' . $tabla . ' existe' . PHP_EOL;
    } catch(Exception $e) {
        echo '✗ Tabla ' . $tabla . ' NO existe: ' . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . 'Verificando datos en tipo_pago:' . PHP_EOL;
try {
    $result = $pdo->query('SELECT * FROM tipo_pago');
    $count = 0;
    foreach($result as $row) {
        echo 'ID: ' . $row['id'] . ' - Nombre: ' . $row['nombre'] . PHP_EOL;
        $count++;
    }
    echo 'Total: ' . $count . ' tipos de pago' . PHP_EOL;
} catch(Exception $e) {
    echo 'Error consultando tipo_pago: ' . $e->getMessage() . PHP_EOL;
}
?>
