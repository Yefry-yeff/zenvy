<?php
/**
 * Script de prueba para la API de Ventas E-commerce
 * 
 * Este script permite probar la creación de facturas desde la página web
 * con todos los datos requeridos: cliente, productos, entrega y pago
 */

// Configuración
$apiUrl = 'http://localhost:8001/api/v1/sales'; // Servidor Zenvy en puerto 8001
$apiKey = 'tu-api-key-aqui'; // Reemplazar con tu API Key real

// NOTA: Este ejemplo usa datos reales de un pedido de Johann Ruiz

/**
 * Función para realizar peticiones a la API
 */
function callApi($url, $apiKey, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'error' => $error
    ];
}

/**
 * Función para mostrar resultados
 */
function displayResult($testName, $result) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "TEST: {$testName}\n";
    echo str_repeat("=", 80) . "\n";
    echo "HTTP Code: {$result['http_code']}\n";
    
    if ($result['error']) {
        echo "cURL Error: {$result['error']}\n";
    }
    
    echo "\nRespuesta:\n";
    echo json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo str_repeat("=", 80) . "\n";
}

// ============================================================================
// TEST: Pedido Real - Johann Ruiz con envío a domicilio
// ============================================================================

echo "\n🧪 Ejecutando prueba de API de Ventas E-commerce con datos reales...\n";

$pedidoRealData = [
    // Datos del cliente
    'customer_name' => 'Johann Ruiz',
    'customer_email' => 'johann_ruiz14@hotmail.com',
    'customer_phone' => '+50497525987',
    
    // Productos
    'items' => [
        [
            'product_id' => 7463, // LIBRETA DE CONEJA
            'quantity' => 1,
            'price' => 1000.00,
            'discount' => 10 // 10% de descuento
        ],
        [
            'product_id' => 7462, // lapiz skole portamina
            'quantity' => 1,
            'price' => 500.00,
            'discount' => 0 // Sin descuento
        ],
        [
            'product_id' => 7461, // evermark permanentt marker (representa costo de envío)
            'quantity' => 1,
            'price' => 50.00,
            'discount' => 0
        ]
    ],
    
    // Información de entrega
    'delivery_type' => 'domicilio',
    'delivery_address' => 'Oficina Francisco Morazán, Tegucigalpa, El centro de AMDC, Frente AMDC',
    
    // Información de pago
    'payment_method' => 'Efectivo',
    'notes' => 'Dejar afuera',
    
    // Totales calculados
    // Producto 1: 1000 * 0.9 = 900
    // Producto 2: 500 * 1 = 500
    // Envío: 50
    // Subtotal: 900 + 500 + 50 = 1450
    // Descuento total: 100
    // ISV (15%): 1450 * 0.15 = 217.50
    // Total: 1450 + 217.50 = 1667.50
    'subtotal' => 1450.00,
    'discount' => 100.00,
    'tax' => 217.50,
    'total' => 1667.50
];

$resultReal = callApi($apiUrl, $apiKey, $pedidoRealData);
displayResult("Pedido Real - Johann Ruiz (Envío a Domicilio)", $resultReal);

// ============================================================================
// RESUMEN DE PRUEBAS
// ============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "RESUMEN DE PRUEBA\n";
echo str_repeat("=", 80) . "\n";

$status = in_array($resultReal['http_code'], [200, 201]) ? '✅ ÉXITO' : 
          ($resultReal['http_code'] === 422 ? '⚠️  VALIDACIÓN' : '❌ ERROR');

echo sprintf(
    "%s - HTTP %d: Pedido Real - Johann Ruiz\n",
    $status,
    $resultReal['http_code']
);

if ($resultReal['http_code'] === 201 && isset($resultReal['response']['data'])) {
    echo "\n📋 Detalles de la Factura:\n";
    echo "   Factura ID: " . $resultReal['response']['data']['factura_id'] . "\n";
    echo "   Cliente: " . $resultReal['response']['data']['cliente'] . "\n";
    echo "   Total: L. " . number_format($resultReal['response']['data']['total'], 2) . "\n";
    echo "   Estado: " . ($resultReal['response']['data']['estado'] == 1 ? 'Completada' : 'Pendiente') . "\n";
}

echo str_repeat("=", 80) . "\n";

// ============================================================================
// INSTRUCCIONES
// ============================================================================

echo "\n📋 INSTRUCCIONES:\n";
echo "1. Configurar la variable \$apiUrl con la URL correcta de tu API\n";
echo "2. Configurar la variable \$apiKey con tu API Key válida\n";
echo "3. Verificar que los product_id (1633, 1634, 9999) existan en la base de datos\n";
echo "   Si el producto de envío (ID 9999) no existe, puedes:\n";
echo "   a) Crearlo en la BD como 'Costo de Envío'\n";
echo "   b) Removerlo del array items y ajustar los totales\n";
echo "4. Ejecutar: php test_api_ventas_ecommerce.php\n";
echo "\n📌 DATOS DEL PEDIDO:\n";
echo "   Cliente: Johann Ruiz\n";
echo "   Email: johann_ruiz14@hotmail.com\n";
echo "   Teléfono: +50497525987\n";
echo "   Dirección: Oficina Francisco Morazán, Tegucigalpa, El centro de AMDC, Frente AMDC\n";
echo "   Método de pago: Efectivo\n";
echo "   Productos:\n";
echo "     - Producto #1633: L. 1,000.00 (10% desc.)\n";
echo "     - Producto #1634: L. 500.00 (sin desc.)\n";
echo "     - Envío: L. 50.00\n";
echo "   Total: L. 1,667.50\n";
echo "\n✅ Test completado\n\n";
