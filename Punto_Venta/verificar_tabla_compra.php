<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ESTRUCTURA TABLA COMPRA ===\n";
$columns = \Illuminate\Support\Facades\DB::select('DESCRIBE compra');
foreach ($columns as $col) {
    echo "Campo: {$col->Field} - Tipo: {$col->Type}\n";
}
?>
