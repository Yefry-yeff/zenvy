<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== Estructura de la tabla producto ===\n\n";

$columns = DB::select('DESCRIBE producto');
foreach($columns as $col) {
    echo sprintf("%-30s %s\n", $col->Field, $col->Type);
}

echo "\n=== Muestra de datos ===\n\n";
$sample = DB::table('producto')->first();
if ($sample) {
    foreach($sample as $key => $value) {
        echo sprintf("%-30s = %s\n", $key, $value);
    }
}
