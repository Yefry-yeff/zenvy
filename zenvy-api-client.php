<?php
/**
 * Cliente PHP para API de Zenvy POS
 * Importa productos desde Zenvy a tu página web
 */

class ZenvyApiClient {
    
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl = 'http://127.0.0.1:8000', $token = null) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }
    
    /**
     * Obtener token JWT desde Zenvy
     */
    public function authenticate($email, $password, $appName = 'Mi Página Web') {
        $url = $this->baseUrl . '/api/v1/auth/token';
        
        $data = [
            'email' => $email,
            'password' => $password,
            'name' => $appName,
            'scope' => 'producto-inventario'
        ];
        
        $response = $this->makeRequest('POST', $url, $data);
        
        if ($response['success'] && isset($response['token'])) {
            $this->token = $response['token'];
            return $this->token;
        }
        
        return false;
    }
    
    /**
     * Obtener todos los productos agrupados por categoría
     * RECOMENDADO - Usa este para importar inventario completo
     */
    public function getInventoryByCategory() {
        $url = $this->baseUrl . '/api/v1/inventory/by-category';
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Obtener todos los productos (paginado)
     */
    public function getInventory($params = []) {
        $queryString = http_build_query($params);
        $url = $this->baseUrl . '/api/v1/inventory' . ($queryString ? '?' . $queryString : '');
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Buscar producto por código de barras
     */
    public function getProductByBarcode($barcode) {
        $url = $this->baseUrl . '/api/v1/inventory/barcode/' . urlencode($barcode);
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Obtener categorías disponibles
     */
    public function getCategories() {
        $url = $this->baseUrl . '/api/v1/inventory/categories';
        return $this->makeRequest('GET', $url);
    }
    
    /**
     * Validar stock disponible antes de vender
     */
    public function validateStock($items) {
        $url = $this->baseUrl . '/api/v1/inventory/validate-stock';
        $data = ['items' => $items];
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Forzar sincronización completa
     */
    public function forceSync() {
        $url = $this->baseUrl . '/api/v1/inventory/sync/force';
        return $this->makeRequest('POST', $url, []);
    }
    
    /**
     * Hacer request HTTP
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init($url);
        
        $headers = ['Content-Type: application/json'];
        
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }
        
        return json_decode($response, true) ?: [
            'success' => false,
            'error' => 'Invalid JSON response',
            'raw' => $response
        ];
    }
}

// ============================================
// EJEMPLO DE USO
// ============================================

// Instanciar cliente
$zenvy = new ZenvyApiClient('http://127.0.0.1:8000');

// 1. Obtener token
$token = $zenvy->authenticate('admin@zenvy.local', 'password', 'Mi Página Web');

if ($token) {
    echo "✅ Token obtenido: " . substr($token, 0, 50) . "...\n\n";
    
    // 2. Obtener inventario por categoría (RECOMENDADO)
    $inventory = $zenvy->getInventoryByCategory();
    
    if ($inventory['success']) {
        echo "✅ Inventario obtenido exitosamente\n";
        echo "Categorías: " . count($inventory['data']) . "\n\n";
        
        foreach ($inventory['data'] as $categoria) {
            echo "📦 Categoría: " . $categoria['categoria_nombre'] . "\n";
            echo "   Total productos: " . $categoria['total_productos'] . "\n";
            echo "   Stock total: " . $categoria['stock_total'] . "\n";
            
            foreach ($categoria['productos'] as $producto) {
                echo "   - {$producto['nombre']} | Stock: {$producto['stock']} | Precio: \${$producto['precio_venta']}\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ Error: " . ($inventory['error'] ?? 'Unknown error') . "\n";
    }
    
    // 3. Buscar producto por código de barras
    $producto = $zenvy->getProductByBarcode('760573020163');
    if ($producto['success']) {
        echo "🔍 Producto encontrado: " . $producto['data']['nombre'] . "\n";
    }
    
} else {
    echo "❌ Error al autenticar\n";
}

?>
