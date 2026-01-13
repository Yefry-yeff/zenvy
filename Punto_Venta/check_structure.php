<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== INVESTIGANDO ESTRUCTURA DE BASE DE DATOS ===\n\n";

// 1. Buscar tablas relacionadas con inventario/stock
echo "1. TABLAS DE INVENTARIO/STOCK:\n";
$inventoryTables = DB::select("SHOW TABLES LIKE '%inventario%' OR SHOW TABLES LIKE '%stock%' OR SHOW TABLES LIKE '%bodega%'");
foreach(DB::select("SHOW TABLES") as $table) {
    $tableName = array_values((array)$table)[0];
    if (stripos($tableName, 'inventario') !== false || 
        stripos($tableName, 'stock') !== false || 
        stripos($tableName, 'bodega') !== false ||
        stripos($tableName, 'existencia') !== false) {
        echo "  • $tableName\n";
        
        // Mostrar estructura
        $columns = DB::select("DESCRIBE $tableName");
        foreach($columns as $col) {
            echo "    - {$col->Field} ({$col->Type})\n";
        }
        echo "\n";
    }
}

// 2. Buscar tablas relacionadas con ventas/facturas
echo "\n2. TABLAS DE VENTAS/FACTURAS:\n";
foreach(DB::select("SHOW TABLES") as $table) {
    $tableName = array_values((array)$table)[0];
    if (stripos($tableName, 'venta') !== false || 
        stripos($tableName, 'factura') !== false || 
        stripos($tableName, 'sale') !== false) {
        echo "  • $tableName\n";
        
        // Mostrar estructura
        $columns = DB::select("DESCRIBE $tableName");
        foreach($columns as $col) {
            echo "    - {$col->Field} ({$col->Type})\n";
        }
        echo "\n";
    }
}

// 3. Verificar si hay una tabla de productos en bodega
echo "\n3. REGISTRO DE STOCK ACTUAL:\n";
if (DB::select("SHOW TABLES LIKE 'producto_bodega'")) {
    echo "  ✓ Tabla 'producto_bodega' existe\n";
    $sample = DB::table('producto_bodega')->first();
    if ($sample) {
        echo "  Ejemplo de registro:\n";
        foreach($sample as $key => $value) {
            echo "    $key = $value\n";
        }
    }
}

echo "\n4. MODELOS EXISTENTES:\n";
$modelsPath = __DIR__ . '/app/Models';
$files = scandir($modelsPath);
foreach($files as $file) {
    if (stripos($file, 'Venta') !== false || 
        stripos($file, 'Factura') !== false || 
        stripos($file, 'Sale') !== false ||
        stripos($file, 'Inventario') !== false ||
        stripos($file, 'Stock') !== false) {
        echo "  • $file\n";
    }
}

echo "\n=== FIN DE LA INVESTIGACIÓN ===\n";
