<?php
// Script CLI puro para asignar el id como código de barras a productos que no lo tengan
// Ejecutar: php asignar_codigobarra_id.php

$host = 'localhost';
$db   = 'db_zenvy'; // Cambia por el nombre real de tu base de datos
$user = 'root'; // Cambia por tu usuario
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

$stmt = $pdo->query("SELECT id, codigo_barra FROM producto WHERE codigo_barra IS NULL OR codigo_barra = ''");
$productos = $stmt->fetchAll(PDO::FETCH_OBJ);
$count = 0;
foreach ($productos as $producto) {
    $update = $pdo->prepare("UPDATE producto SET codigo_barra = ? WHERE id = ?");
    $update->execute([(string)$producto->id, $producto->id]);
    $count++;
}
echo "Actualizados $count productos sin código de barras.\n";
