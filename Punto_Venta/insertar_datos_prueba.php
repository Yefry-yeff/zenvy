<?php

$pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

// Agregar algunos datos de prueba
echo "Agregando datos de prueba..." . PHP_EOL;

// Insertar una tienda de prueba si no existe
$stmt = $pdo->prepare('INSERT IGNORE INTO tienda (id, denominacion_social, estado_id) VALUES (1, "Tienda Principal", 1)');
$stmt->execute();

// Insertar una bodega principal
$stmt = $pdo->prepare('INSERT IGNORE INTO bodega (id, nombre, tienda_id, principal, estado_id) VALUES (1, "Bodega Principal Tienda 1", 1, 1, 1)');
$stmt->execute();

// Insertar un segmento
$stmt = $pdo->prepare('INSERT IGNORE INTO segmento (id, descripcion, bodega_id) VALUES (1, "Segmento A", 1)');
$stmt->execute();

// Insertar un producto
$stmt = $pdo->prepare('INSERT IGNORE INTO producto (id, nombre, codigo_barra, precio_base, isv, estado_id) VALUES (1, "Producto Prueba", "123456789", 100.00, 15, 1)');
$stmt->execute();

// Insertar una sección
$stmt = $pdo->prepare('INSERT IGNORE INTO seccion (id, descripcion, numeracion, segmento_id, estado_id) VALUES (1, "Sección A", "SEC-001", 1, 1)');
$stmt->execute();

// Insertar stock en recibido_bodega
$stmt = $pdo->prepare('INSERT IGNORE INTO recibido_bodega (id, producto_id, seccion_id, cantidad_compra_lote, cantidad_inicial_seccion, cantidad_disponible, fecha_recibido, estado_id, users_registro_id) VALUES (1, 1, 1, 10, 10, 10, CURDATE(), 1, 1)');
$stmt->execute();

// Insertar un usuario de prueba
$stmt = $pdo->prepare('INSERT IGNORE INTO users (id, name, email, password, tienda_id) VALUES (1, "Usuario Prueba", "admin@test.com", "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi", 1)');
$stmt->execute();

echo "Datos de prueba agregados exitosamente!" . PHP_EOL;
echo "Tienda: Tienda Principal (ID: 1)" . PHP_EOL;
echo "Bodega Principal: Bodega Principal Tienda 1" . PHP_EOL;
echo "Producto: Producto Prueba (Código: 123456789)" . PHP_EOL;
echo "Stock disponible: 10 unidades" . PHP_EOL;
echo "Usuario: admin@test.com (password: password)" . PHP_EOL;
