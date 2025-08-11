<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    $result = $pdo->query('SHOW TABLES');
    echo "Tablas en la base de datos:" . PHP_EOL;
    foreach($result as $row) {
        if (strpos($row[0], 'bodega') !== false || strpos($row[0], 'seccion') !== false || strpos($row[0], 'segmento') !== false || strpos($row[0], 'recibido') !== false || strpos($row[0], 'stock') !== false || strpos($row[0], 'producto') !== false) {
            echo "*** " . $row[0] . PHP_EOL;
        } else {
            echo $row[0] . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
