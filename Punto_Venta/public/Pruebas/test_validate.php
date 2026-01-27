<?php
require __DIR__.'/vendor/autoload.php';

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
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

// 1. Get token
echo "1. Obteniendo token...\n";
$auth = makeRequest($baseUrl . '/v1/auth/token', 'POST', [
    'api_key' => $apiKey,
    'api_secret' => $apiSecret
]);
$token = $auth['body']['data']['token'];
echo "Token: " . substr($token, 0, 50) . "...\n\n";

// 2. Validate stock
echo "2. Validando stock para producto 1659...\n";
$validation = makeRequest($baseUrl . '/v1/inventory/validate-stock', 'POST', [
    'items' => [
        [
            'sku' => '1659',
            'quantity' => 1
        ]
    ]
], $token);

echo "Status Code: {$validation['code']}\n";
echo "Response:\n";
print_r($validation['body']);
echo "\n";
