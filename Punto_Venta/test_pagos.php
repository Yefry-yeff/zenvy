<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Verificar pagos de hoy
$pagosHoy = DB::table('factura_has_pago as fhp')
    ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
    ->join('factura as f', 'fhp.factura_id', '=', 'f.id')
    ->select('tp.nombre', DB::raw('SUM(fhp.pago_recibido) as total'))
    ->whereDate('f.created_at', today())
    ->groupBy('tp.id', 'tp.nombre')
    ->get();

echo "Pagos de hoy:\n";
print_r($pagosHoy->toArray());

// Verificar últimas facturas
$ultimasFacturas = DB::table('factura')
    ->select('id', 'numero_factura', 'total', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "\n\nÚltimas 5 facturas:\n";
print_r($ultimasFacturas->toArray());

// Verificar si hay facturas de hoy
$facturasHoy = DB::table('factura')
    ->whereDate('created_at', today())
    ->count();

echo "\n\nFacturas de hoy: " . $facturasHoy . "\n";
