<?php
/**
 * Ejemplo de Cliente API REST POS
 * 
 * Este archivo demuestra cómo consumir el API desde PHP
 */

class PosApiClient
{
    private string $baseUrl;
    private ?string $token = null;
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $baseUrl, string $apiKey, string $apiSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Autenticarse y obtener token JWT
     */
    public function authenticate(): bool
    {
        $response = $this->request('POST', '/v1/auth/token', [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret
        ], false);

        if ($response['success'] && isset($response['data']['token'])) {
            $this->token = $response['data']['token'];
            return true;
        }

        return false;
    }

    /**
     * Obtener inventario
     */
    public function getInventory(array $filters = [], int $perPage = 50): array
    {
        $query = http_build_query(array_merge($filters, ['per_page' => $perPage]));
        return $this->request('GET', "/v1/inventory?{$query}");
    }

    /**
     * Obtener producto por SKU
     */
    public function getProductBySku(string $sku): array
    {
        return $this->request('GET', "/v1/inventory/{$sku}");
    }

    /**
     * Validar disponibilidad de stock
     */
    public function validateStock(array $items): array
    {
        return $this->request('POST', '/v1/inventory/validate-stock', [
            'items' => $items
        ]);
    }

    /**
     * Crear venta / Facturar pedido
     */
    public function createSale(array $saleData): array
    {
        return $this->request('POST', '/v1/sales', $saleData);
    }

    /**
     * Consultar estado de venta
     */
    public function getSale(int $facturaId): array
    {
        return $this->request('GET', "/v1/sales/{$facturaId}");
    }

    /**
     * Anular venta
     */
    public function cancelSale(int $facturaId, string $motivo): array
    {
        return $this->request('PUT', "/v1/sales/{$facturaId}/cancel", [
            'motivo' => $motivo
        ]);
    }

    /**
     * Realizar request HTTP
     */
    private function request(string $method, string $endpoint, array $data = [], bool $requiresAuth = true): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($requiresAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'CURL_ERROR',
                    'message' => $error
                ]
            ];
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_JSON',
                    'message' => 'Respuesta inválida del servidor'
                ]
            ];
        }

        return $result;
    }
}

// ========================================
// EJEMPLOS DE USO
// ========================================

// Configuración
$apiUrl = 'http://localhost/api';
$apiKey = 'pk_your_api_key_here';
$apiSecret = 'sk_your_api_secret_here';

$client = new PosApiClient($apiUrl, $apiKey, $apiSecret);

// 1. Autenticarse
echo "1. Autenticando...\n";
if ($client->authenticate()) {
    echo "✓ Autenticación exitosa\n\n";
} else {
    die("✗ Error de autenticación\n");
}

// 2. Obtener inventario
echo "2. Consultando inventario...\n";
$inventory = $client->getInventory(['available' => true], 10);
if ($inventory['success']) {
    echo "✓ Productos encontrados: " . count($inventory['data']) . "\n";
    foreach ($inventory['data'] as $product) {
        echo "  - {$product['sku']}: {$product['nombre']} (Stock: {$product['stock_actual']})\n";
    }
    echo "\n";
} else {
    echo "✗ Error: " . $inventory['error']['message'] . "\n\n";
}

// 3. Consultar producto específico
echo "3. Consultando producto por SKU...\n";
$product = $client->getProductBySku('PROD-001');
if ($product['success']) {
    echo "✓ Producto: {$product['data']['nombre']}\n";
    echo "  Precio: \${$product['data']['precio_venta']}\n";
    echo "  Stock: {$product['data']['stock_actual']}\n\n";
} else {
    echo "✗ Error: " . $product['error']['message'] . "\n\n";
}

// 4. Validar stock
echo "4. Validando disponibilidad de stock...\n";
$stockValidation = $client->validateStock([
    ['sku' => 'PROD-001', 'quantity' => 2],
    ['sku' => 'PROD-002', 'quantity' => 1]
]);
if ($stockValidation['success']) {
    $available = $stockValidation['data']['available'] ? 'Sí' : 'No';
    echo "✓ Stock disponible: {$available}\n";
    foreach ($stockValidation['data']['items'] as $item) {
        $status = $item['is_available'] ? '✓' : '✗';
        echo "  {$status} {$item['sku']}: Solicitado {$item['requested']}, Disponible {$item['available']}\n";
    }
    echo "\n";
} else {
    echo "✗ Error: " . $stockValidation['error']['message'] . "\n\n";
}

// 5. Crear venta
echo "5. Creando venta...\n";
$saleData = [
    'external_order_id' => 'WEB-' . time(),
    'cliente' => [
        'nombre' => 'Cliente Ejemplo',
        'email' => 'cliente@example.com',
        'telefono' => '+504 9876-5432',
        'direccion' => 'Tegucigalpa, Honduras'
    ],
    'items' => [
        [
            'sku' => 'PROD-001',
            'cantidad' => 2,
            'precio_unitario' => 100.00
        ]
    ],
    'subtotal' => 200.00,
    'descuento' => 0,
    'impuestos' => 30.00,
    'total' => 230.00,
    'forma_pago' => 'tarjeta_credito',
    'metadatos' => [
        'origen' => 'e-commerce',
        'ip_cliente' => '192.168.1.100'
    ]
];

$sale = $client->createSale($saleData);
if ($sale['success']) {
    echo "✓ Venta creada exitosamente\n";
    echo "  ID Factura: {$sale['data']['factura_id']}\n";
    echo "  Número: {$sale['data']['numero_factura']}\n";
    echo "  Estado: {$sale['data']['estado']}\n";
    echo "  Total: \${$sale['data']['total']}\n\n";
    
    $facturaId = $sale['data']['factura_id'];
    
    // 6. Consultar la venta creada
    echo "6. Consultando venta creada...\n";
    $saleDetail = $client->getSale($facturaId);
    if ($saleDetail['success']) {
        echo "✓ Estado actual: {$saleDetail['data']['estado']}\n";
        echo "  Cliente: {$saleDetail['data']['cliente']['nombre']}\n";
        echo "  Items: " . count($saleDetail['data']['items']) . "\n\n";
    }
    
    // 7. Anular la venta (opcional - descomentar para probar)
    /*
    echo "7. Anulando venta...\n";
    $cancel = $client->cancelSale($facturaId, 'Prueba de anulación desde API');
    if ($cancel['success']) {
        echo "✓ Venta anulada exitosamente\n";
        echo "  Estado: {$cancel['data']['estado']}\n";
        echo "  Motivo: {$cancel['data']['motivo_anulacion']}\n\n";
    } else {
        echo "✗ Error: " . $cancel['error']['message'] . "\n\n";
    }
    */
    
} else {
    echo "✗ Error: " . $sale['error']['message'] . "\n";
    if (isset($sale['error']['details'])) {
        echo "  Detalles: " . json_encode($sale['error']['details'], JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "Pruebas completadas\n";
echo "========================================\n";
