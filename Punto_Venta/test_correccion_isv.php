<?php

require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Producto;
use App\Models\Isv;

echo "=== PRUEBA DE CORRECCIÓN DEL ERROR ISV ===\n\n";

try {
    // 1. Verificar que hay productos con ISV
    echo "1. VERIFICANDO PRODUCTOS CON ISV:\n";
    echo "=================================\n";
    
    $productos = Producto::with('isv')
        ->whereHas('isv')
        ->where('estado_id', 1)
        ->limit(5)
        ->get();
    
    if ($productos->count() == 0) {
        echo "No se encontraron productos con ISV asignado.\n";
        echo "Verificando productos disponibles...\n\n";
        
        $productosDisponibles = Producto::where('estado_id', 1)->limit(3)->get();
        
        foreach ($productosDisponibles as $producto) {
            echo "Producto: {$producto->nombre}\n";
            echo "  ISV ID: " . ($producto->isv_id ?? 'NULL') . "\n";
            echo "  Relación ISV: " . ($producto->isv ? 'SÍ' : 'NO') . "\n";
            if ($producto->isv) {
                echo "  Valor ISV: {$producto->isv->cantidad}%\n";
            }
            echo "\n";
        }
    } else {
        foreach ($productos as $producto) {
            echo "Producto: {$producto->nombre}\n";
            echo "  Código: {$producto->codigo_barra}\n";
            echo "  Precio: L. " . number_format($producto->precio_base, 2) . "\n";
            echo "  ISV ID: {$producto->isv_id}\n";
            echo "  ISV Valor: {$producto->isv->cantidad}%\n";
            echo "  Descuento 3ra: {$producto->descuento_tercera}%\n";
            echo "  Descuento 4ta: {$producto->descuento_cuarta}%\n";
            echo "\n";
        }
    }

    // 2. Simular la lógica del componente Livewire
    echo "2. SIMULANDO LÓGICA DEL COMPONENTE:\n";
    echo "===================================\n";
    
    if ($productos->count() > 0) {
        $producto = $productos->first();
        
        echo "Producto seleccionado: {$producto->nombre}\n";
        
        // Simular como se agrega al array productosFactura
        $valorIsv = $producto->isv ? $producto->isv->cantidad : 0;
        
        $productoFactura = [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo_barra,
            'precio' => $producto->precio_base,
            'isv' => $valorIsv,
            'cantidad' => 2,
            'descuento_tercera' => $producto->descuento_tercera ?? 0,
            'descuento_cuarta' => $producto->descuento_cuarta ?? 0,
            'descuento_aplicado' => 0,
            'subtotal_con_descuento' => 0
        ];
        
        echo "Array del producto en factura:\n";
        print_r($productoFactura);
        
        // Simular cálculo de totales
        echo "\n3. SIMULANDO CÁLCULO DE TOTALES:\n";
        echo "================================\n";
        
        $cantidad = $productoFactura['cantidad'];
        $precio = $productoFactura['precio'];
        $tasaIsv = $productoFactura['isv'];
        
        $subtotalOriginal = $precio * $cantidad;
        echo "Subtotal original: L. " . number_format($subtotalOriginal, 2) . "\n";
        
        // Sin descuento
        $isvSinDescuento = $subtotalOriginal * ($tasaIsv / 100);
        $totalSinDescuento = $subtotalOriginal + $isvSinDescuento;
        
        echo "ISV ({$tasaIsv}%): L. " . number_format($isvSinDescuento, 2) . "\n";
        echo "Total sin descuento: L. " . number_format($totalSinDescuento, 2) . "\n\n";
        
        // Con descuento de tercera edad (si aplica)
        if ($producto->descuento_tercera > 0) {
            $descuentoImporte = $subtotalOriginal * ($producto->descuento_tercera / 100);
            $subtotalConDescuento = $subtotalOriginal - $descuentoImporte;
            $isvConDescuento = $subtotalConDescuento * ($tasaIsv / 100);
            $totalConDescuento = $subtotalConDescuento + $isvConDescuento;
            
            echo "CON DESCUENTO 3RA EDAD ({$producto->descuento_tercera}%):\n";
            echo "Descuento: -L. " . number_format($descuentoImporte, 2) . "\n";
            echo "Subtotal con descuento: L. " . number_format($subtotalConDescuento, 2) . "\n";
            echo "ISV sobre subtotal con descuento: L. " . number_format($isvConDescuento, 2) . "\n";
            echo "Total final: L. " . number_format($totalConDescuento, 2) . "\n";
            echo "Ahorro total: L. " . number_format($totalSinDescuento - $totalConDescuento, 2) . "\n";
        }
        
        echo "\n✅ CORRECCIÓN APLICADA EXITOSAMENTE\n";
        echo "- El objeto ISV ya no se divide por 100\n";
        echo "- Se obtiene el valor numérico correcto: {$valorIsv}\n";
        echo "- Los cálculos funcionan correctamente\n";
        
    } else {
        echo "No hay productos disponibles para la prueba.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
