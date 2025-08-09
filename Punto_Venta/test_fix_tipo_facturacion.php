<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\CAIService;
use Illuminate\Support\Facades\DB;

echo "=== PRUEBA RÁPIDA DESPUÉS DEL FIX ===\n\n";

try {
    $caiService = new CAIService();
    
    // Generar número CAI
    echo "1. Generando número CAI...\n";
    $resultadoCAI = $caiService->obtenerSiguienteNumeroFactura();
    
    echo "Número generado: {$resultadoCAI['numero_factura']}\n";
    echo "CAI ID: {$resultadoCAI['cai_id']}\n";
    echo "Restantes: {$resultadoCAI['cantidad_restante']}\n\n";
    
    // Simular creación de factura con todos los campos requeridos
    echo "2. Creando factura en base de datos...\n";
    $facturaId = DB::table('factura')->insertGetId([
        'cai_id' => $resultadoCAI['cai_id'],
        'tipo_facturacion_id' => 1, // ✅ Campo corregido
        'numero_factura' => $resultadoCAI['numero_factura'],
        'nombre_cliente' => "Cliente Test Fix",
        'rtn' => null,
        'sub_total' => 40.00,
        'sub_total_grabado' => 40.00,
        'sub_total_exento' => 0.00,
        'isv' => 4.80,
        'total' => 44.80,
        'credito' => 0.00,
        'fecha_emision' => date('Y-m-d'),
        'estado_factura_id' => 1,
        'users_id' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✅ Factura creada exitosamente con ID: $facturaId\n";
    
    // Verificar la factura creada
    echo "\n3. Verificando factura creada...\n";
    $factura = DB::table('factura as f')
        ->join('cai as c', 'f.cai_id', '=', 'c.id')
        ->select('f.*', 'c.cai')
        ->where('f.id', $facturaId)
        ->first();
        
    if ($factura) {
        echo "ID: {$factura->id}\n";
        echo "Número: {$factura->numero_factura}\n";
        echo "Cliente: {$factura->nombre_cliente}\n";
        echo "Total: L.{$factura->total}\n";
        echo "CAI: {$factura->cai}\n";
        echo "Tipo Facturación ID: {$factura->tipo_facturacion_id}\n";
    }
    
    echo "\n✅ Sistema funcionando correctamente después del fix!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
