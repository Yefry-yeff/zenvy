<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo 'Datos en tabla tipo_facturacion:' . PHP_EOL;
    try {
        $result = $pdo->query('SELECT * FROM tipo_facturacion');
        foreach($result as $row) {
            echo 'ID: ' . $row['id'] . ' - Nombre: ' . (isset($row['nombre']) ? $row['nombre'] : 'Sin nombre') . PHP_EOL;
        }
    } catch(Exception $e) {
        echo 'Error en tipo_facturacion: ' . $e->getMessage() . PHP_EOL;
    }

    echo PHP_EOL . 'Datos en tabla estado_factura:' . PHP_EOL;
    try {
        $result = $pdo->query('SELECT * FROM estado_factura');
        foreach($result as $row) {
            echo 'ID: ' . $row['id'] . ' - Nombre: ' . (isset($row['nombre']) ? $row['nombre'] : 'Sin nombre') . PHP_EOL;
        }
    } catch(Exception $e) {
        echo 'Error en estado_factura: ' . $e->getMessage() . PHP_EOL;
    }
    
} catch(Exception $e) {
    echo 'Error de conexión: ' . $e->getMessage() . PHP_EOL;
}
?>
