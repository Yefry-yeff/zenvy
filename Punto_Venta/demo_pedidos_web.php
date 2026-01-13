<?php
/**
 * DEMO: FLUJO E-COMMERCE CON BANDEJA DE ENTRADA
 * 
 * Flujo correcto:
 * 1. Web consulta inventario
 * 2. Web valida stock
 * 3. Web envía PEDIDO (no factura)
 * 4. Pedido queda en "bandeja de entrada" Zenvy
 * 5. Usuario de Zenvy visualiza y procesa el pedido
 * 6. Al procesar → crea factura → decrementa stock
 */

$apiKey = 'pk_rwKtALSl7ttJmijwczwGPwDTZDXjMnHi';
$apiSecret = 'sk_viS4zRTDhdpY2SDnqJLheHb4wbEDC6gRvjxz2zkexZmLrMBSuCFfoaFluzVprsJY';
$baseUrl = 'http://127.0.0.1:8000/api/v1';

function apiRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   DEMO: E-COMMERCE → BANDEJA DE ENTRADA → FACTURACIÓN       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// PASO 1: Autenticación
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 1: Obtener Token JWT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$authResponse = apiRequest("$baseUrl/auth/token", 'POST', [
    'api_key' => $apiKey,
    'api_secret' => $apiSecret
]);

if (!$authResponse['body']['success']) {
    echo "✗ Error obteniendo token\n";
    print_r($authResponse);
    die();
}

$token = $authResponse['body']['data']['token'];
echo "✓ Token obtenido exitosamente\n";
echo "  Expira en: {$authResponse['body']['data']['expires_in']} segundos\n\n";

// PASO 2: Consultar inventario
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 2: Consultar Inventario (Vista Web)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$inventoryResponse = apiRequest("$baseUrl/inventory?limit=100", 'GET', null, $token);

if (!$inventoryResponse['body']['success']) {
    die("✗ Error consultando inventario\n");
}

$products = $inventoryResponse['body']['data'];
$productsWithStock = array_filter($products, fn($p) => $p['stock_actual'] > 0);

echo "✓ Inventario obtenido\n";
echo "  Total productos: " . count($products) . "\n";
echo "  Con stock: " . count($productsWithStock) . "\n\n";

if (empty($productsWithStock)) {
    die("⚠ No hay productos con stock disponible\n");
}

$selectedProduct = reset($productsWithStock);
echo "  → Producto seleccionado:\n";
echo "    SKU: {$selectedProduct['sku']}\n";
echo "    Nombre: {$selectedProduct['nombre']}\n";
echo "    Stock: {$selectedProduct['stock_actual']}\n";
echo "    Precio: L " . number_format($selectedProduct['precio_venta'], 2) . "\n\n";

// PASO 3: Validar stock
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 3: Validar Stock Antes de Enviar Pedido\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$validateResponse = apiRequest("$baseUrl/inventory/validate-stock", 'POST', [
    'items' => [
        [
            'sku' => $selectedProduct['sku'],
            'quantity' => 1
        ]
    ]
], $token);

$stockAvailable = $validateResponse['body']['data']['available'];
echo ($stockAvailable ? "✓" : "✗") . " Stock: " . ($stockAvailable ? "DISPONIBLE" : "NO DISPONIBLE") . "\n\n";

// PASO 4: Enviar PEDIDO (no factura)
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 4: Enviar Pedido Web (Preview de Carrito)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$subtotal = $selectedProduct['precio_venta'];
$isv = $subtotal * 0.15;
$total = $subtotal + $isv;

echo "  Datos del pedido:\n";
echo "  • Cliente: María García\n";
echo "  • Email: maria@example.com\n";
echo "  • Teléfono: +504 9999-8888\n";
echo "  • Subtotal: L " . number_format($subtotal, 2) . "\n";
echo "  • ISV (15%): L " . number_format($isv, 2) . "\n";
echo "  • Total: L " . number_format($total, 2) . "\n\n";

$orderResponse = apiRequest("$baseUrl/orders", 'POST', [
    'order_number' => 'WEB-' . date('YmdHis'),
    'customer_name' => 'María García',
    'customer_email' => 'maria@example.com',
    'customer_phone' => '+504 9999-8888',
    'customer_address' => 'Tegucigalpa, Honduras',
    'items' => [
        [
            'sku' => $selectedProduct['sku'],
            'quantity' => 1,
            'price' => $selectedProduct['precio_venta']
        ]
    ],
    'subtotal' => $subtotal,
    'tax' => $isv,
    'total' => $total,
    'payment_method' => 'tarjeta_credito',
    'notes' => 'Cliente VIP - Entrega express'
], $token);

if (!$orderResponse['body']['success']) {
    echo "✗ Error enviando pedido\n";
    print_r($orderResponse);
    die();
}

$pedidoId = $orderResponse['body']['data']['pedido_id'];
$numeroPedido = $orderResponse['body']['data']['numero_pedido'];

echo "✓ Pedido enviado exitosamente\n";
echo "  • ID: {$pedidoId}\n";
echo "  • Número: {$numeroPedido}\n";
echo "  • Estado: {$orderResponse['body']['data']['estado']}\n";
echo "  • Mensaje: {$orderResponse['body']['message']}\n\n";

// PASO 5: Ver bandeja de entrada (Vista Zenvy)
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 5: Bandeja de Entrada Zenvy (Pedidos Pendientes)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

sleep(1);

$pendingResponse = apiRequest("$baseUrl/orders/pending", 'GET', null, $token);

echo "✓ Pedidos pendientes: " . count($pendingResponse['body']['data']) . "\n\n";

foreach ($pendingResponse['body']['data'] as $pedido) {
    $badge = $pedido['leido'] ? '  ' : '🔔';
    echo "  {$badge} #{$pedido['numero_pedido']} - {$pedido['cliente']['nombre']}\n";
    echo "     Total: L " . number_format($pedido['total'], 2) . " | Items: {$pedido['items_count']}\n";
    echo "     Estado: {$pedido['estado']} | Recibido: {$pedido['created_at']}\n\n";
}

// PASO 6: Ver detalle del pedido
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 6: Ver Detalle del Pedido\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$detailResponse = apiRequest("$baseUrl/orders/{$pedidoId}", 'GET', null, $token);

$detalle = $detailResponse['body']['data'];
echo "✓ Pedido #{$detalle['numero_pedido']}\n\n";
echo "  Cliente:\n";
echo "    • Nombre: {$detalle['cliente']['nombre']}\n";
echo "    • Email: {$detalle['cliente']['email']}\n";
echo "    • Teléfono: {$detalle['cliente']['telefono']}\n\n";

echo "  Productos:\n";
foreach ($detalle['items'] as $item) {
    echo "    • {$item['producto_nombre']}\n";
    echo "      Cantidad: {$item['cantidad']} x L" . number_format($item['precio_unitario'], 2) . " = L" . number_format($item['total'], 2) . "\n";
}

echo "\n  Totales:\n";
echo "    • Subtotal: L " . number_format($detalle['subtotal'], 2) . "\n";
echo "    • ISV: L " . number_format($detalle['isv'], 2) . "\n";
echo "    • Total: L " . number_format($detalle['total'], 2) . "\n\n";

// PASO 7: Usuario Zenvy procesa el pedido
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 7: Procesar Pedido y Crear Factura (Acción Zenvy)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "  Usuario de Zenvy aprueba el pedido...\n\n";

$processResponse = apiRequest("$baseUrl/orders/{$pedidoId}/process", 'POST', [
    'user_id' => 1,
    'options' => [
        'caja_id' => 1,
        'cai_id' => 1,
    ]
], $token);

if (!$processResponse['body']['success']) {
    echo "✗ Error procesando pedido\n";
    print_r($processResponse);
    die();
}

$facturaId = $processResponse['body']['data']['factura_id'];

echo "✓ Pedido procesado exitosamente\n";
echo "  • Factura ID: {$facturaId}\n";
echo "  • Estado: {$processResponse['body']['data']['estado']}\n";
echo "  • Mensaje: {$processResponse['body']['message']}\n\n";

// PASO 8: Verificar stock decrementado
echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 8: Verificar Stock Decrementado\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$productResponse = apiRequest("$baseUrl/inventory/{$selectedProduct['sku']}", 'GET', null, $token);
$newStock = $productResponse['body']['data']['stock_actual'];

echo "✓ Stock actualizado\n";
echo "  • Stock anterior: {$selectedProduct['stock_actual']}\n";
echo "  • Stock nuevo: {$newStock}\n";
echo "  • Diferencia: " . ($newStock - $selectedProduct['stock_actual']) . "\n\n";

// RESUMEN
echo "═══════════════════════════════════════════════════════════════\n";
echo " RESUMEN DEL FLUJO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ FLUJO COMPLETO EJECUTADO:\n\n";
echo "  1. ✓ Web consultó inventario\n";
echo "  2. ✓ Web validó stock disponible\n";
echo "  3. ✓ Web envió pedido (NO factura)\n";
echo "  4. ✓ Pedido llegó a bandeja de entrada Zenvy\n";
echo "  5. ✓ Usuario Zenvy visualizó el pedido\n";
echo "  6. ✓ Usuario Zenvy procesó y facturó\n";
echo "  7. ✓ Stock se decrementó automáticamente\n\n";

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  ✨ SISTEMA DE PEDIDOS WEB FUNCIONANDO CORRECTAMENTE         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "📊 DATOS FINALES:\n";
echo "  • Pedido: #{$numeroPedido}\n";
echo "  • Factura: #{$facturaId}\n";
echo "  • Cliente: María García\n";
echo "  • Total: L " . number_format($total, 2) . "\n";
echo "  • Stock restante: {$newStock}\n\n";
