<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    echo 'Estructura de la tabla factura:' . PHP_EOL;
    $result = $pdo->query('DESCRIBE factura');
    foreach($result as $row) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - Null: ' . $row['Null'] . ' - Default: ' . $row['Default'] . PHP_EOL;
    }
    
    echo PHP_EOL . 'Verificando tabla cai:' . PHP_EOL;
    try {
        $result = $pdo->query('SELECT * FROM cai LIMIT 5');
        foreach($result as $row) {
            echo 'CAI ID: ' . $row['id'] . PHP_EOL;
        }
    } catch(Exception $e) {
        echo 'Tabla cai no existe o error: ' . $e->getMessage() . PHP_EOL;
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
