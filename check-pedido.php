<?php

require __DIR__ . '/Punto_Venta/vendor/autoload.php';

$app = require_once __DIR__ . '/Punto_Venta/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pedido = App\Models\PedidoWeb::where('numero_pedido', 'PW-2026-0039')->first();

if ($pedido) {
    echo "============================================\n";
    echo "Pedido: " . $pedido->numero_pedido . "\n";
    echo "============================================\n";
    echo "Método de pago: " . $pedido->metodo_pago . "\n";
    echo "Tipo de metadata: " . gettype($pedido->metadata) . "\n";
    echo "\n--- METADATA COMPLETA ---\n";
    print_r($pedido->metadata);
    echo "\n--- METADATA EN JSON ---\n";
    echo json_encode($pedido->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    echo "\n--- VERIFICACIONES ---\n";
    echo "¿Es array? " . (is_array($pedido->metadata) ? 'SI' : 'NO') . "\n";
    echo "¿Existe transfer_info? " . (isset($pedido->metadata['transfer_info']) ? 'SI' : 'NO') . "\n";
    
    if (isset($pedido->metadata['transfer_info'])) {
        echo "\n--- TRANSFER_INFO ---\n";
        print_r($pedido->metadata['transfer_info']);
    }
} else {
    echo "Pedido PW-2026-0039 no encontrado\n";
}
