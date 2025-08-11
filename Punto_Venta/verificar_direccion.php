<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    echo "=== ESTRUCTURA TABLA DIRECCION ===\n";
    $result = $pdo->query('DESCRIBE direccion');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\n=== DATOS DE EJEMPLO ===\n";
    $result = $pdo->query('SELECT * FROM direccion LIMIT 3');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}\n";
        foreach ($row as $campo => $valor) {
            if ($campo != 'id') {
                echo "  - $campo: $valor\n";
            }
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
