<?php

require_once __DIR__ . '/vendor/autoload.php';

// Configurar Laravel básico
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\SincronizacionProductosService;
use Illuminate\Support\Facades\DB;

echo "🧪 Probando sincronización de productos Valencia...\n\n";

try {
    $service = SincronizacionProductosService::obtenerInstancia();
    
    // Obtener algunos productos de Valencia para probar
    echo "📋 Productos disponibles en Valencia:\n";
    $productosValencia = $service->obtenerProductosValencia()->take(5);
    
    foreach($productosValencia as $producto) {
        echo "ID: {$producto->id} - {$producto->nombre}\n";
        echo "  Marca ID: {$producto->marca_id}\n";
        echo "  Unidad ID: {$producto->unidad_medida_compra_id}\n";
        echo "  Subcategoría ID: {$producto->sub_categoria_id}\n";
        echo "  Precio: L. {$producto->precio_base}\n\n";
    }
    
    // Probar sincronización de un producto específico (usar el primero)
    if ($productosValencia->count() > 0) {
        $primerProducto = $productosValencia->first();
        echo "🔄 Intentando sincronizar producto ID: {$primerProducto->id}\n";
        
        $resultado = $service->sincronizarProducto($primerProducto->id);
        
        if ($resultado['success']) {
            echo "✅ {$resultado['mensaje']}\n";
            echo "   ID en Zenvy: {$resultado['id_zenvy']}\n";
        } else {
            echo "❌ {$resultado['mensaje']}\n";
        }
    }
    
    // Mostrar estadísticas
    echo "\n📊 Estadísticas de sincronización:\n";
    $stats = $service->obtenerEstadisticasSincronizacion();
    echo "Total en Valencia: {$stats['total_valencia']}\n";
    echo "Total sincronizados: {$stats['total_sincronizados']}\n";
    echo "Pendientes: {$stats['pendientes']}\n";
    echo "Porcentaje: {$stats['porcentaje_sincronizado']}%\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}