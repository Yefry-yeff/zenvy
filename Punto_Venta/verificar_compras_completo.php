<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TABLAS RELACIONADAS CON COMPRAS ===\n";
$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (strpos($tableName, 'compra') !== false) {
        echo $tableName . "\n";
    }
}

echo "\n=== TABLA COMPRA_HAS_PRODUCTO ===\n";
if (in_array('compra_has_producto', array_map(function($t) { return array_values((array)$t)[0]; }, $tables))) {
    $columns = \Illuminate\Support\Facades\DB::select('DESCRIBE compra_has_producto');
    foreach ($columns as $col) {
        echo "Campo: {$col->Field} - Tipo: {$col->Type}\n";
    }
}
?>
