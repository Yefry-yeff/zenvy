<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    echo "=== ESTRUCTURA TABLA ESTADO ===\n";
    $result = $pdo->query('DESCRIBE estado');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n=== DATOS ESTADO ===\n";
    $result = $pdo->query('SELECT * FROM estado LIMIT 5');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        foreach ($row as $campo => $valor) {
            echo "$campo: $valor, ";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
