<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo 'Estructura de tipo_facturacion:' . PHP_EOL;
    $result = $pdo->query('DESCRIBE tipo_facturacion');
    foreach($result as $row) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
    
    echo PHP_EOL . 'Estructura de estado_factura:' . PHP_EOL;
    $result = $pdo->query('DESCRIBE estado_factura');
    foreach($result as $row) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
