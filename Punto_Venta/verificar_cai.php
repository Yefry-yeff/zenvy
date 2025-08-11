<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');

    echo "=== VERIFICACIÓN DE TABLAS CAI ===\n\n";

    // Verificar estructura de gestion_cai
    echo "1. Estructura de gestion_cai:\n";
    $result = $pdo->query('DESCRIBE gestion_cai');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['Field']} ({$row['Type']})\n";
    }

    // Verificar estructura de cai
    echo "\n2. Estructura de cai:\n";
    $result = $pdo->query('DESCRIBE cai');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   - {$row['Field']} ({$row['Type']})\n";
    }

    // Verificar datos existentes en gestion_cai
    echo "\n3. Datos en gestion_cai:\n";
    $result = $pdo->query('SELECT * FROM gestion_cai');
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "   ID: {$row['id']}, Número actual: {$row['numero_actual']}, Base: {$row['numero_base']}\n";
            echo "   Cantidad no utilizada: {$row['cantidad_no_utilizada']}, Estado: {$row['estado_id']}\n";
        }
    } else {
        echo "   No hay datos en gestion_cai\n";
    }

    // Verificar datos existentes en cai
    echo "\n4. Datos en cai:\n";
    $result = $pdo->query('SELECT * FROM cai');
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "   ID: {$row['id']}, CAI: {$row['cai']}\n";
            echo "   Rango: {$row['rango_inicio']} - {$row['rango_final']}\n";
            echo "   Estado: {$row['estado_id']}, Tienda: {$row['tienda_id']}\n";
            echo "   ---\n";
        }
    } else {
        echo "   No hay datos en cai\n";
    }

    // Verificar estados
    echo "\n5. Estados disponibles:\n";
    $result = $pdo->query('SELECT * FROM estado');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "   ID: {$row['id']} - Nombre: {$row['nombre']}\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
