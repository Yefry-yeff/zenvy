<?php

require __DIR__ . '/Punto_Venta/vendor/autoload.php';

$app = require_once __DIR__ . '/Punto_Venta/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$numeroPedido = 'PW-2026-0039';

$pedido = App\Models\PedidoWeb::where('numero_pedido', $numeroPedido)->first();

if (!$pedido) {
    echo "❌ Pedido {$numeroPedido} no encontrado\n";
    exit(1);
}

echo "============================================\n";
echo "ACTUALIZAR PEDIDO CON TRANSFER_INFO\n";
echo "============================================\n\n";

echo "Pedido: {$pedido->numero_pedido}\n";
echo "Método de pago: {$pedido->metodo_pago}\n\n";

// Datos de la transferencia a agregar
$transferInfo = [
    'account_bank' => 'BAC',
    'account_type' => 'ahorro',
    'account_number' => '7777725',
    'account_holder' => 'Paperland',
    'transfer_date' => '2026-01-01'
];

// Obtener metadata actual
$metadata = $pedido->metadata ?? [];

echo "Metadata ANTES:\n";
print_r($metadata);

// Agregar transfer_info
$metadata['transfer_info'] = $transferInfo;
$metadata['delivery_address'] = 'El centro de AMDC';
$metadata['shipping_cost'] = 60;

echo "\nMetadata DESPUÉS:\n";
print_r($metadata);

// Actualizar el pedido
$pedido->metadata = $metadata;
$pedido->save();

echo "\n✅ Pedido actualizado exitosamente\n\n";

// Verificar
$pedidoActualizado = App\Models\PedidoWeb::where('numero_pedido', $numeroPedido)->first();
echo "VERIFICACIÓN:\n";
echo "¿Existe transfer_info? " . (isset($pedidoActualizado->metadata['transfer_info']) ? 'SI ✓' : 'NO ✗') . "\n";

if (isset($pedidoActualizado->metadata['transfer_info'])) {
    echo "\nDatos de transferencia guardados:\n";
    print_r($pedidoActualizado->metadata['transfer_info']);
}
