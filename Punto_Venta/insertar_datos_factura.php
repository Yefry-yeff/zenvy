<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    
    echo 'Insertando datos básicos...' . PHP_EOL;
    
    // Insertar tipos de facturación
    echo 'Insertando tipos de facturación...' . PHP_EOL;
    $pdo->exec("INSERT INTO tipo_facturacion (id, nombre) VALUES 
                (1, 'Venta Normal') 
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
    
    $pdo->exec("INSERT INTO tipo_facturacion (id, nombre) VALUES 
                (2, 'Crédito') 
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
    
    // Insertar estados de factura
    echo 'Insertando estados de factura...' . PHP_EOL;
    $pdo->exec("INSERT INTO estado_factura (id, nombre) VALUES 
                (1, 'Activa') 
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
    
    $pdo->exec("INSERT INTO estado_factura (id, nombre) VALUES 
                (2, 'Anulada') 
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
    
    echo 'Datos insertados correctamente!' . PHP_EOL;
    
    // Verificar inserción
    echo PHP_EOL . 'Verificando datos insertados:' . PHP_EOL;
    
    echo 'Tipos de facturación:' . PHP_EOL;
    $result = $pdo->query('SELECT * FROM tipo_facturacion');
    foreach($result as $row) {
        echo 'ID: ' . $row['id'] . ' - Nombre: ' . $row['nombre'] . PHP_EOL;
    }
    
    echo PHP_EOL . 'Estados de factura:' . PHP_EOL;
    $result = $pdo->query('SELECT * FROM estado_factura');
    foreach($result as $row) {
        echo 'ID: ' . $row['id'] . ' - Nombre: ' . $row['nombre'] . PHP_EOL;
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
