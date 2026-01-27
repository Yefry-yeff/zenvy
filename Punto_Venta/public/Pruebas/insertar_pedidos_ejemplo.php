<?php
/**
 * Script para insertar pedidos web de ejemplo
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoWeb;
use App\Models\PedidoWebItem;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

echo "🔄 Insertando pedidos web de ejemplo...\n\n";

// Obtener algunos productos con stock
$productos = Producto::whereHas('recibidosBodega', function($q) {
    $q->where('estado_id', 1)->where('cantidad_disponible', '>', 0);
})
->take(5)
->get();

if ($productos->isEmpty()) {
    die("❌ No hay productos con stock disponible\n");
}

echo "✓ Encontrados " . $productos->count() . " productos con stock\n\n";

// Crear 3 pedidos de ejemplo
$pedidos = [
    [
        'numero_pedido' => 'WEB-' . date('Ymd') . '-001',
        'cliente_nombre' => 'Juan Carlos Martínez',
        'cliente_email' => 'juan.martinez@example.com',
        'cliente_telefono' => '+504 9888-7777',
        'cliente_rtn' => '0801-1990-12345',
        'cliente_direccion' => 'Colonia Palmira, Tegucigalpa',
        'metodo_pago' => 'tarjeta_credito',
        'notas' => 'Cliente frecuente - Envío express solicitado',
        'leido' => false,
    ],
    [
        'numero_pedido' => 'WEB-' . date('Ymd') . '-002',
        'cliente_nombre' => 'María Elena Rodríguez',
        'cliente_email' => 'maria.rodriguez@gmail.com',
        'cliente_telefono' => '+504 9777-6666',
        'cliente_direccion' => 'Residencial Los Castaños, San Pedro Sula',
        'metodo_pago' => 'transferencia',
        'notas' => 'Necesita factura con RTN',
        'leido' => false,
    ],
    [
        'numero_pedido' => 'WEB-' . date('Ymd') . '-003',
        'cliente_nombre' => 'Roberto Sánchez',
        'cliente_email' => 'roberto.s@hotmail.com',
        'cliente_telefono' => '+504 9666-5555',
        'metodo_pago' => 'efectivo',
        'notas' => 'Recoger en tienda',
        'leido' => true, // Este ya fue leído
    ],
];

foreach ($pedidos as $index => $pedidoData) {
    echo "📦 Creando pedido " . ($index + 1) . ": {$pedidoData['numero_pedido']}\n";
    
    // Seleccionar 1-3 productos aleatorios
    $numItems = rand(1, 3);
    $itemsSeleccionados = $productos->random(min($numItems, $productos->count()));
    
    $subtotal = 0;
    $items = [];
    
    foreach ($itemsSeleccionados as $producto) {
        $cantidad = rand(1, 3);
        $precioUnidad = $producto->precio1 ?: $producto->precio_base ?: 100;
        $subtotalItem = $cantidad * $precioUnidad;
        $isvItem = $subtotalItem * 0.15;
        $totalItem = $subtotalItem + $isvItem;
        
        $items[] = [
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnidad,
            'subtotal' => $subtotalItem,
            'isv' => $isvItem,
            'total' => $totalItem,
        ];
        
        $subtotal += $subtotalItem;
        
        echo "   • {$producto->nombre} x{$cantidad} @ L" . number_format($precioUnidad, 2) . "\n";
    }
    
    $isv = $subtotal * 0.15;
    $total = $subtotal + $isv;
    
    // Crear pedido
    $pedido = PedidoWeb::create([
        'numero_pedido' => $pedidoData['numero_pedido'],
        'estado' => 'pendiente',
        'cliente_nombre' => $pedidoData['cliente_nombre'],
        'cliente_email' => $pedidoData['cliente_email'],
        'cliente_telefono' => $pedidoData['cliente_telefono'],
        'cliente_rtn' => $pedidoData['cliente_rtn'] ?? null,
        'cliente_direccion' => $pedidoData['cliente_direccion'] ?? null,
        'subtotal' => $subtotal,
        'descuento' => 0,
        'isv' => $isv,
        'total' => $total,
        'metodo_pago' => $pedidoData['metodo_pago'],
        'notas' => $pedidoData['notas'],
        'leido' => $pedidoData['leido'],
        'metadata' => [
            'source' => 'demo_script',
            'ip' => '192.168.1.100',
            'user_agent' => 'Demo/1.0',
        ],
    ]);
    
    // Crear items
    foreach ($items as $itemData) {
        $itemData['pedido_web_id'] = $pedido->id;
        PedidoWebItem::create($itemData);
    }
    
    echo "   ✓ Total: L" . number_format($total, 2) . " (" . count($items) . " items)\n\n";
}

echo "\n✅ Pedidos de ejemplo insertados exitosamente\n";
echo "🔔 Pedidos no leídos: " . PedidoWeb::where('leido', false)->count() . "\n";
echo "📋 Total pedidos pendientes: " . PedidoWeb::where('estado', 'pendiente')->count() . "\n\n";
echo "👉 Accede a: http://127.0.0.1:8000/pedidos-web\n";
