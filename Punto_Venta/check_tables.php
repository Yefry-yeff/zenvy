<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== ESTRUCTURA DE TABLAS CLAVE ===\n\n";

echo "1. FACTURA_HAS_PRODUCTO (items de facturas):\n";
$cols = DB::select('DESCRIBE factura_has_producto');
foreach($cols as $c) {
    echo sprintf("  %-30s %s\n", $c->Field, $c->Type);
}

echo "\n2. RECIBIDO_BODEGA (control de stock):\n";
$cols2 = DB::select('DESCRIBE recibido_bodega');
foreach($cols2 as $c) {
    echo sprintf("  %-30s %s\n", $c->Field, $c->Type);
}

echo "\n3. EJEMPLO DE STOCK DISPONIBLE:\n";
$stock = DB::table('recibido_bodega')
    ->where('cantidad_disponible', '>', 0)
    ->where('estado_id', 1)
    ->orderBy('cantidad_disponible', 'desc')
    ->limit(3)
    ->get();

foreach($stock as $item) {
    echo "  • Producto ID: {$item->producto_id} | Disponible: {$item->cantidad_disponible} unidades\n";
}

echo "\n4. EJEMPLO DE FACTURA RECIENTE:\n";
$factura = DB::table('factura')->latest('id')->first();
if ($factura) {
    echo "  ID: {$factura->id}\n";
    echo "  Total: L {$factura->total}\n";
    echo "  Estado: {$factura->estado_factura_id}\n";
    echo "  Fecha: {$factura->fecha_emision}\n";
}

echo "\n";
