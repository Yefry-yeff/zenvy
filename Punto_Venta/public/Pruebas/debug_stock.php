<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$productoId = 1659; // ABACO DE ANIMALES CON RELOJ BENSSINI BS-1577

echo "\nVerificando stock del producto ID: $productoId\n\n";

$stock = DB::table('recibido_bodega')
    ->where('producto_id', $productoId)
    ->where('estado_id', 1)
    ->get();

echo "Lotes de bodega:\n";
foreach($stock as $lote) {
    echo "  • Lote ID: {$lote->id}\n";
    echo "    Cantidad disponible: {$lote->cantidad_disponible}\n";
    echo "    Estado: {$lote->estado_id}\n";
    echo "    Fecha: {$lote->fecha_recibido}\n\n";
}

$totalStock = DB::table('recibido_bodega')
    ->where('producto_id', $productoId)
    ->where('estado_id', 1)
    ->where('cantidad_disponible', '>', 0)
    ->sum('cantidad_disponible');

echo "Stock total: $totalStock unidades\n\n";
