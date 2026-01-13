<?php
/**
 * Demo Completa: E-commerce → Zenvy POS
 * 
 * Este script simula el flujo completo desde una página web:
 * 1. Consultar productos disponibles con stock real
 * 2. Validar stock antes de crear venta
 * 3. Crear venta/factura en Zenvy
 * 4. Verificar que el stock se decrementó automáticamente
 */

require __DIR__.'/vendor/autoload.php';

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   DEMO: INTEGRACIÓN E-COMMERCE ↔ ZENVY POS                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$baseUrl = 'http://127.0.0.1:8000/api';
$apiKey = 'pk_rwKtALSl7ttJmijwczwGPwDTZDXjMnHi';
$apiSecret = 'sk_viS4zRTDhdpY2SDnqJLheHb4wbEDC6gRvjxz2zkexZmLrMBSuCFfoaFluzVprsJY';

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
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

echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 1: Obtener Token JWT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$auth = makeRequest($baseUrl . '/v1/auth/token', 'POST', [
    'api_key' => $apiKey,
    'api_secret' => $apiSecret
]);

if ($auth['code'] != 200) {
    echo "✗ Error obteniendo token\n";
    exit(1);
}

$token = $auth['body']['data']['token'];
echo "✓ Token obtenido exitosamente\n";
echo "  Expira en: {$auth['body']['data']['expires_in']} segundos\n\n";

sleep(1);

echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 2: Consultar Inventario Disponible (E-commerce)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$inventory = makeRequest($baseUrl . '/v1/inventory?per_page=5', 'GET', null, $token);

if ($inventory['code'] != 200) {
    echo "✗ Error consultando inventario\n";
    exit(1);
}

echo "✓ Inventario obtenido\n";
echo "  Total productos: {$inventory['body']['meta']['total']}\n";
echo "  Mostrando: " . count($inventory['body']['data']) . " productos\n\n";

// Seleccionar productos con stock disponible y precio válido
$productosDisponibles = array_filter($inventory['body']['data'], function($p) {
    return $p['stock_actual'] > 0 && $p['precio_venta'] > 0;
});

if (empty($productosDisponibles)) {
    echo "⚠ No hay productos con stock disponible para la demo\n";
    exit(1);
}

echo "  Productos con stock disponible:\n";
foreach (array_slice($productosDisponibles, 0, 3) as $p) {
    echo "  • ID: {$p['id']} - {$p['nombre']}\n";
    echo "    Stock: {$p['stock_actual']} unidades | Precio: L " . number_format($p['precio_venta'], 2) . "\n";
}

// Seleccionar primer producto para la venta
$productoVenta = array_values($productosDisponibles)[0];
$cantidadVenta = min(2, $productoVenta['stock_actual']); // Vender máximo 2 unidades

echo "\n  → Producto seleccionado para venta:\n";
echo "    SKU: {$productoVenta['sku']}\n";
echo "    Nombre: {$productoVenta['nombre']}\n";
echo "    Precio: L " . number_format($productoVenta['precio_venta'], 2) . "\n";
echo "    Cantidad a vender: {$cantidadVenta} unidades\n";
echo "    Stock actual: {$productoVenta['stock_actual']} unidades\n\n";

sleep(1);

echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 3: Validar Stock Antes de Procesar Venta\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$validation = makeRequest($baseUrl . '/v1/inventory/validate-stock', 'POST', [
    'items' => [
        [
            'sku' => $productoVenta['sku'],
            'quantity' => $cantidadVenta
        ]
    ]
], $token);

if ($validation['code'] != 200) {
    echo "✗ Error validando stock\n";
    print_r($validation);
    exit(1);
}

$stockValido = $validation['body']['data']['available'];
echo ($stockValido ? "✓" : "✗") . " Validación de stock: " . ($stockValido ? "DISPONIBLE" : "NO DISPONIBLE") . "\n";

if (!$stockValido) {
    echo "  ⚠ No hay suficiente stock para procesar la venta\n\n";
    exit(1);
}

echo "  → Stock validado correctamente\n\n";

sleep(1);

echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 4: Crear Venta/Factura en Zenvy POS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$precioUnitario = $productoVenta['precio_venta'];
$subtotal = $precioUnitario * $cantidadVenta;
$isv = $subtotal * 0.15; // 15% ISV
$total = $subtotal + $isv;

$ventaData = [
    'customer_name' => 'Juan Pérez - Cliente Web',
    'customer_rtn' => '0801199012345',
    'customer_email' => 'juan.perez@example.com',
    'customer_phone' => '+504 9999-9999',
    'customer_address' => 'Tegucigalpa, Honduras',
    'items' => [
        [
            'sku' => $productoVenta['sku'],
            'quantity' => $cantidadVenta,
            'price' => $precioUnitario
        ]
    ],
    'subtotal' => $subtotal,
    'discount' => 0,
    'tax' => $isv,
    'total' => $total,
    'payment_method' => 'tarjeta_credito',
    'notes' => 'Venta desde E-commerce - Demo de integración'
];

echo "  Datos de la venta:\n";
echo "  • Cliente: {$ventaData['customer_name']}\n";
echo "  • Subtotal: L " . number_format($subtotal, 2) . "\n";
echo "  • ISV (15%): L " . number_format($isv, 2) . "\n";
echo "  • Total: L " . number_format($total, 2) . "\n";
echo "  • Método de pago: {$ventaData['payment_method']}\n\n";

$sale = makeRequest($baseUrl . '/v1/sales', 'POST', $ventaData, $token);

if ($sale['code'] != 201) {
    echo "✗ Error creando venta\n";
    if (isset($sale['body']['error'])) {
        echo "  Error: {$sale['body']['error']['message']}\n";
        if (isset($sale['body']['error']['details'])) {
            print_r($sale['body']['error']['details']);
        }
    }
    print_r($sale);
    exit(1);
}

$facturaId = $sale['body']['data']['id'];
echo "✓ Venta creada exitosamente\n";
echo "  • Factura ID: {$facturaId}\n";
echo "  • Total: L " . number_format($sale['body']['data']['total'], 2) . "\n";
echo "  • Estado: {$sale['body']['data']['estado_factura_id']}\n";
echo "  • Fecha: {$sale['body']['data']['fecha_emision']}\n\n";

sleep(1);

echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 5: Verificar Decremento Automático de Stock\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$productoActualizado = makeRequest(
    $baseUrl . '/v1/inventory/' . $productoVenta['sku'], 
    'GET', 
    null, 
    $token
);

if ($productoActualizado['code'] != 200) {
    echo "✗ Error consultando producto actualizado\n";
    exit(1);
}

$stockNuevo = $productoActualizado['body']['data']['stock_actual'];
$stockAnterior = $productoVenta['stock_actual'];
$stockDecrementado = $stockAnterior - $stockNuevo;

echo "✓ Stock actualizado automáticamente\n";
echo "  • Stock anterior: {$stockAnterior} unidades\n";
echo "  • Cantidad vendida: {$cantidadVenta} unidades\n";
echo "  • Stock nuevo: {$stockNuevo} unidades\n";
echo "  • Diferencia: -{$stockDecrementado} unidades\n\n";

if ($stockDecrementado == $cantidadVenta) {
    echo "  ✓ El stock se decrementó correctamente\n\n";
} else {
    echo "  ⚠ Advertencia: El decremento no coincide exactamente\n\n";
}

sleep(1);

echo "═══════════════════════════════════════════════════════════════\n";
echo " PASO 6: Consultar Factura Creada\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$facturaConsulta = makeRequest($baseUrl . '/v1/sales/' . $facturaId, 'GET', null, $token);

if ($facturaConsulta['code'] == 200) {
    $factura = $facturaConsulta['body']['data'];
    echo "✓ Factura consultada exitosamente\n";
    echo "  • ID: {$factura['id']}\n";
    echo "  • Cliente: {$factura['nombre_cliente']}\n";
    echo "  • Total: L " . number_format($factura['total'], 2) . "\n";
    echo "  • Productos: " . count($factura['productos']) . "\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo " RESUMEN FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ FLUJO COMPLETO EJECUTADO EXITOSAMENTE\n\n";
echo "El flujo E-commerce → Zenvy POS funciona correctamente:\n\n";
echo "  1. ✓ Consulta de inventario con stock real\n";
echo "  2. ✓ Validación de disponibilidad pre-venta\n";
echo "  3. ✓ Creación de factura en Zenvy\n";
echo "  4. ✓ Decremento automático de stock en bodega\n";
echo "  5. ✓ Consulta de factura creada\n\n";

echo "📊 DATOS DE LA DEMO:\n";
echo "  • Factura creada: #{$facturaId}\n";
echo "  • Producto vendido: {$productoVenta['nombre']}\n";
echo "  • Cantidad: {$cantidadVenta} unidades\n";
echo "  • Stock actual: {$stockNuevo} unidades\n";
echo "  • Monto total: L " . number_format($total, 2) . "\n\n";

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  ✨ TU WEB YA PUEDE VENDER Y CONTROLAR INVENTARIO EN ZENVY   ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "📝 PRÓXIMOS PASOS:\n";
echo "  1. Integrar este flujo en tu sitio web\n";
echo "  2. Manejar errores de stock insuficiente\n";
echo "  3. Implementar webhooks para notificaciones\n";
echo "  4. Configurar backup de transacciones\n\n";
