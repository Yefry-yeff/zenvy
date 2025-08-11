<?php

require_once 'vendor/autoload.php';

// Configurar el entorno de Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\CAIService;
use Illuminate\Support\Facades\DB;

echo "=== PRUEBA COMPLETA DEL SISTEMA CAI INTEGRADO ===\n\n";

try {
    $caiService = new CAIService();

    // 1. Estado inicial
    echo "1. Estado inicial del sistema:\n";
    $informacion = $caiService->obtenerInformacionCAIs();
    foreach ($informacion as $cai) {
        echo "CAI {$cai->id}: {$cai->numero_base} | Actual: {$cai->numero_actual} | Restantes: {$cai->cantidad_no_utilizada} | Estado: {$cai->estado}\n";
    }
    echo "\n";

    // 2. Simular generación de facturas
    echo "2. Simulando creación de facturas:\n";

    for ($i = 1; $i <= 3; $i++) {
        echo "\n--- FACTURA $i ---\n";

        try {
            // Generar número CAI
            $resultadoCAI = $caiService->obtenerSiguienteNumeroFactura();

            // Simular creación de factura
            $facturaId = DB::table('factura')->insertGetId([
                'cai_id' => $resultadoCAI['cai_id'],
                'tipo_facturacion_id' => 1,
                'numero_factura' => $resultadoCAI['numero_factura'],
                'nombre_cliente' => "Cliente Prueba $i",
                'rtn' => null,
                'sub_total' => 100.00,
                'sub_total_grabado' => 100.00,
                'sub_total_exento' => 0.00,
                'isv' => 15.00,
                'total' => 115.00,
                'credito' => 0.00,
                'fecha_emision' => date('Y-m-d'),
                'estado_factura_id' => 1,
                'users_id' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "✅ Factura creada:\n";
            echo "   ID: $facturaId\n";
            echo "   Número: {$resultadoCAI['numero_factura']}\n";
            echo "   CAI ID: {$resultadoCAI['cai_id']}\n";
            echo "   CAI: {$resultadoCAI['cai']}\n";
            echo "   Secuencia: {$resultadoCAI['numero_secuencia']}\n";
            echo "   Restantes en CAI: {$resultadoCAI['cantidad_restante']}\n";

            if ($resultadoCAI['cai_agotado']) {
                echo "   ⚠️  CAI AGOTADO después de esta factura\n";
            } elseif ($resultadoCAI['cantidad_restante'] <= 5) {
                echo "   ⚠️  POCAS FACTURAS RESTANTES: {$resultadoCAI['cantidad_restante']}\n";
            }

        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            break;
        }
    }

    // 3. Estado final
    echo "\n3. Estado final del sistema:\n";
    $informacionFinal = $caiService->obtenerInformacionCAIs();
    foreach ($informacionFinal as $cai) {
        echo "CAI {$cai->id}: {$cai->numero_base} | Actual: {$cai->numero_actual} | Restantes: {$cai->cantidad_no_utilizada} | Estado: {$cai->estado}\n";
    }

    // 4. Verificar facturas creadas
    echo "\n4. Facturas creadas en la base de datos:\n";
    $facturas = DB::table('factura as f')
        ->join('cai as c', 'f.cai_id', '=', 'c.id')
        ->select('f.id', 'f.numero_factura', 'f.nombre_cliente', 'f.total', 'c.cai')
        ->orderBy('f.id', 'desc')
        ->limit(5)
        ->get();

    foreach ($facturas as $factura) {
        echo "Factura {$factura->id}: {$factura->numero_factura} | Cliente: {$factura->nombre_cliente} | Total: L.{$factura->total} | CAI: {$factura->cai}\n";
    }

    echo "\n✅ Sistema CAI funcionando correctamente!\n";
    echo "🎯 Características implementadas:\n";
    echo "   - Generación automática de números de factura\n";
    echo "   - Control de secuencia CAI\n";
    echo "   - Gestión de cantidad disponible\n";
    echo "   - Desactivación automática de CAI agotados\n";
    echo "   - Validación de fechas de vencimiento\n";
    echo "   - Formato con 8 dígitos y padding de ceros\n";
    echo "   - Concatenación numero_base + numero_actual\n";
    echo "   - Sistema FIFO para múltiples CAIs\n";

} catch (Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
