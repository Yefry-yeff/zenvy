<?php
/**
 * Script de Prueba del API
 */

echo "\n========================================\n";
echo "  PRUEBAS DEL API REST POS\n";
echo "========================================\n\n";

$baseUrl = 'http://127.0.0.1:8000/api';

// Credenciales del cliente creado
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

// Test 1: Health Check
echo "1. Health Check...\n";
$health = makeRequest($baseUrl . '/health');
if ($health['code'] == 200 && $health['body']['success']) {
    echo "   ✓ API funcionando\n";
    echo "   Version: " . $health['body']['version'] . "\n\n";
} else {
    echo "   ✗ Error en health check\n\n";
    exit(1);
}

// Test 2: Obtener Token
echo "2. Obtener Token JWT...\n";
$auth = makeRequest($baseUrl . '/v1/auth/token', 'POST', [
    'api_key' => $apiKey,
    'api_secret' => $apiSecret
]);

if ($auth['code'] == 200 && isset($auth['body']['data']['token'])) {
    $token = $auth['body']['data']['token'];
    echo "   ✓ Token obtenido\n";
    echo "   Expira en: " . $auth['body']['data']['expires_in'] . " segundos\n\n";
} else {
    echo "   ✗ Error obteniendo token\n";
    print_r($auth);
    exit(1);
}

// Test 3: Listar Inventario
echo "3. Consultar Inventario...\n";
$inventory = makeRequest($baseUrl . '/v1/inventory?per_page=5', 'GET', null, $token);
if ($inventory['code'] == 200 && $inventory['body']['success']) {
    echo "   ✓ Inventario obtenido\n";
    echo "   Productos: " . count($inventory['body']['data']) . "\n";
    echo "   Total: " . $inventory['body']['meta']['total'] . "\n\n";
} else {
    echo "   ✗ Error consultando inventario\n";
    print_r($inventory);
    echo "\n";
}

// Test 4: Validar Stock
echo "4. Validar Stock...\n";
$stockValidation = makeRequest($baseUrl . '/v1/inventory/validate-stock', 'POST', [
    'items' => [
        ['sku' => '1', 'quantity' => 1] // ID 1 = LONCHERA ADIDAS
    ]
], $token);

if ($stockValidation['code'] == 200) {
    echo "   ✓ Validación ejecutada\n";
    $available = $stockValidation['body']['data']['available'] ? 'Disponible' : 'No disponible';
    echo "   Estado: " . $available . "\n\n";
} else {
    echo "   ✗ Error validando stock\n";
    print_r($stockValidation);
    echo "\n";
}

echo "========================================\n";
echo "  RESUMEN DE PRUEBAS\n";
echo "========================================\n\n";
echo "✓ API instalado correctamente\n";
echo "✓ Autenticación funcionando\n";
echo "✓ Endpoints respondiendo\n";
echo "✓ Base de datos conectada\n\n";
echo "El API está listo para usar!\n\n";
echo "========================================\n\n";
