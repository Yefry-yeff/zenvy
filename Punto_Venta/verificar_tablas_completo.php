<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_zenvy', 'root', '');
    echo "=== TABLAS DISPONIBLES ===\n";
    $result = $pdo->query('SHOW TABLES');
    $tables = [];
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
        echo "- {$row[0]}\n";
    }
    
    echo "\n=== BUSCANDO TABLA DE SUCURSALES ===\n";
    $sucursalTables = array_filter($tables, function($table) {
        return stripos($table, 'sucur') !== false || stripos($table, 'tienda') !== false || stripos($table, 'store') !== false;
    });
    
    if (!empty($sucursalTables)) {
        foreach ($sucursalTables as $table) {
            echo "✓ Tabla relacionada encontrada: '$table'\n";
            try {
                $result = $pdo->query("DESCRIBE $table");
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    echo "  - {$row['Field']} ({$row['Type']})\n";
                }
            } catch (Exception $e) {
                echo "  Error describiendo tabla: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "❌ No se encontraron tablas relacionadas con sucursales\n";
    }
    
    echo "\n=== VERIFICANDO FACTURA ===\n";
    $result = $pdo->query("SELECT * FROM factura LIMIT 1");
    $factura = $result->fetch(PDO::FETCH_ASSOC);
    if ($factura) {
        echo "Campos disponibles en factura:\n";
        foreach ($factura as $campo => $valor) {
            echo "  - $campo\n";
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
