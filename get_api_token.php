<?php
/**
 * Script para obtener token JWT de autenticación
 */

// Obtener API Key y Secret de la base de datos
require __DIR__ . '/Punto_Venta/vendor/autoload.php';

// Cargar el framework Laravel
$app = require_once __DIR__ . '/Punto_Venta/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔑 Obteniendo credenciales API...\n\n";

try {
    $client = DB::table('api_clients')
        ->where('is_active', 1)
        ->first();
    
    if (!$client) {
        echo "❌ No hay clientes API activos en la base de datos.\n";
        exit(1);
    }
    
    echo "✅ Cliente encontrado:\n";
    echo "   ID: {$client->id}\n";
    echo "   Nombre: {$client->name}\n";
    echo "   API Key: {$client->api_key}\n";
    echo "   API Secret: {$client->api_secret}\n";
    echo "\n";
    
    // Ahora vamos a generar el token usando cURL
    echo "🔐 Generando token JWT...\n\n";
    
    $authUrl = 'http://localhost:8001/api/v1/auth/token';
    
    $authData = [
        'api_key' => $client->api_key,
        'api_secret' => $client->api_secret
    ];
    
    $ch = curl_init($authUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Error de conexión: {$error}\n";
        echo "   ⚠️  ¿Está el servidor Laravel corriendo en http://localhost:8000?\n";
        echo "   Ejecuta: php artisan serve\n";
        exit(1);
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['data']['token'])) {
        echo "✅ Token generado exitosamente!\n\n";
        echo "Token: {$result['data']['token']}\n";
        echo "Expira en: {$result['data']['expires_in']} segundos\n\n";
        
        // Guardar el token para usar en el test
        file_put_contents(__DIR__ . '/api_token.txt', $result['data']['token']);
        echo "💾 Token guardado en: api_token.txt\n";
        
    } else {
        echo "❌ Error al generar token (HTTP {$httpCode}):\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
