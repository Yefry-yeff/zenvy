<?php
/**
 * Script para crear credenciales API para Paperland
 */

require __DIR__ . '/Punto_Venta/vendor/autoload.php';
$app = require_once __DIR__ . '/Punto_Venta/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Auth\ApiAuthService;
use Illuminate\Support\Facades\DB;

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        CREAR CREDENCIALES API - PAPERLAND                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

try {
    $service = new ApiAuthService();
    
    // Verificar si ya existe un cliente para Paperland
    $existingClient = DB::table('api_clients')
        ->where('name', 'LIKE', '%Paperland%')
        ->orWhere('name', 'LIKE', '%paperland%')
        ->first();
    
    if ($existingClient) {
        echo "⚠️  Ya existe un cliente API para Paperland:\n";
        echo "   ID: {$existingClient->id}\n";
        echo "   Nombre: {$existingClient->name}\n\n";
        echo "🔑 API Key existente:\n";
        echo "{$existingClient->api_key}\n\n";
        echo "❓ ¿Deseas rotar el secret? El secret actual no se puede recuperar.\n";
        echo "   [s/n]: ";
        $rotate = strtolower(trim(fgets(STDIN)));
        
        if ($rotate === 's' || $rotate === 'y') {
            $result = $service->rotateSecret($existingClient->id);
            echo "\n✅ Secret rotado exitosamente!\n\n";
            echo "╔═══════════════════════════════════════════════════════════════╗\n";
            echo "║  NUEVO SECRET - GUÁRDALO AHORA                                ║\n";
            echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
            echo "🔑 API Key:\n{$existingClient->api_key}\n\n";
            echo "🔐 API Secret:\n{$result['new_secret']}\n\n";
            $apiKey = $existingClient->api_key;
            $apiSecret = $result['new_secret'];
        } else {
            echo "\n❌ No se puede continuar sin el secret.\n";
            echo "   Para obtener un nuevo secret, ejecuta nuevamente y acepta rotarlo.\n";
            exit(1);
        }
    } else {
        // Crear nuevo cliente
        echo "🔨 Creando nuevo cliente API para Paperland...\n\n";
        $client = $service->createClient('Paperland Web - API Client', [
            'is_active' => true,
            'rate_limit_per_minute' => 100,
            'allowed_ips' => json_encode(['127.0.0.1', '::1'])
        ]);
        
        echo "✅ Cliente creado exitosamente!\n\n";
        echo "╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║  CREDENCIALES - GUÁRDALAS AHORA                               ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
        echo "📋 ID: {$client->id}\n";
        echo "📛 Nombre: {$client->name}\n\n";
        echo "🔑 API Key:\n{$client->api_key}\n\n";
        echo "🔐 API Secret:\n{$client->plain_secret}\n\n";
        
        $apiKey = $client->api_key;
        $apiSecret = $client->plain_secret;
    }
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  PASO 2: OBTENER TOKEN JWT                                    ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    // Generar JWT automáticamente
    echo "🔄 Generando token JWT...\n\n";
    
    $ch = curl_init('http://127.0.0.1:8000/api/v1/auth/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'api_key' => $apiKey,
            'api_secret' => $apiSecret
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Error de conexión: {$error}\n";
        echo "\n⚠️  Asegúrate de que el servidor esté corriendo:\n";
        echo "   cd Punto_Venta\n";
        echo "   php artisan serve --port=8000\n\n";
        exit(1);
    }
    
    if ($httpCode !== 200) {
        echo "❌ Error obteniendo token (HTTP {$httpCode}):\n";
        echo $response . "\n\n";
        exit(1);
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['data']['token'])) {
        echo "❌ Respuesta inesperada del servidor:\n";
        echo $response . "\n\n";
        exit(1);
    }
    
    $jwtToken = $data['data']['token'];
    $expiresIn = $data['data']['expires_in'] ?? 'N/A';
    
    echo "✅ Token JWT obtenido exitosamente!\n\n";
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  TOKEN JWT PARA PAPERLAND                                     ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "🎫 Token JWT:\n";
    echo "{$jwtToken}\n\n";
    echo "⏰ Expira en: {$expiresIn} días\n\n";
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  CONFIGURACIÓN PARA PAPERLAND (.env)                          ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "# Credenciales de API\n";
    echo "ZENVY_API_KEY={$apiKey}\n";
    echo "ZENVY_API_SECRET={$apiSecret}\n\n";
    
    echo "# Token JWT (válido por {$expiresIn} días)\n";
    echo "ZENVY_JWT_TOKEN={$jwtToken}\n\n";
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  CÓMO USAR EN PAPERLAND                                       ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "1. Copia el token JWT en tu código PHP:\n\n";
    echo "   \$token = '{$jwtToken}';\n\n";
    
    echo "2. Usa el token en tus peticiones HTTP:\n\n";
    echo "   \$ch = curl_init('http://127.0.0.1:8000/api/v1/inventory/by-category');\n";
    echo "   curl_setopt_array(\$ch, [\n";
    echo "       CURLOPT_RETURNTRANSFER => true,\n";
    echo "       CURLOPT_HTTPHEADER => [\n";
    echo "           'Authorization: Bearer {$jwtToken}',\n";
    echo "           'Accept: application/json'\n";
    echo "       ]\n";
    echo "   ]);\n\n";
    
    echo "3. Cuando expire, genera uno nuevo:\n\n";
    echo "   POST http://127.0.0.1:8000/api/v1/auth/token\n";
    echo "   {\n";
    echo "       \"api_key\": \"{$apiKey}\",\n";
    echo "       \"api_secret\": \"{$apiSecret}\"\n";
    echo "   }\n\n";
    
    echo "✅ ¡Listo! Ya puedes consumir el API de Zenvy desde Paperland.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n";
    exit(1);
}
