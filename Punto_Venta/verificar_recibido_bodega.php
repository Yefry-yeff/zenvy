<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TABLA RECIBIDO_BODEGA ===\n";
$cols = \Illuminate\Support\Facades\DB::select('DESCRIBE recibido_bodega');
foreach ($cols as $c) {
    if (strpos($c->Field, 'user') !== false || strpos($c->Field, 'registro') !== false) {
        echo "{$c->Field} - {$c->Type}\n";
    }
}

echo "\n=== REGISTROS RECIENTES EN RECIBIDO_BODEGA ===\n";
$registros = \Illuminate\Support\Facades\DB::table('recibido_bodega as rb')
    ->join('users as u', 'rb.users_registro_id', '=', 'u.id')
    ->whereMonth('rb.created_at', now()->month)
    ->whereYear('rb.created_at', now()->year)
    ->select('u.name', 'rb.created_at', 'rb.users_registro_id')
    ->orderBy('rb.created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($registros as $reg) {
    echo "Usuario: {$reg->name} - Fecha: {$reg->created_at}\n";
}
?>
