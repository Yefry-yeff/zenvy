<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Auth\ApiAuthService;

echo "\n========================================\n";
echo "  CREANDO CLIENTE API\n";
echo "========================================\n\n";

try {
    $authService = app(ApiAuthService::class);
    
    $client = $authService->createClient('E-commerce Principal', [
        'rate_limit_per_minute' => 100,
        'is_active' => true
    ]);
    
    echo "✓ Cliente API creado exitosamente!\n\n";
    echo "ID: " . $client->id . "\n";
    echo "Nombre: " . $client->name . "\n";
    echo "API Key: " . $client->api_key . "\n";
    echo "API Secret: " . $client->plain_secret . "\n";
    echo "Rate Limit: " . $client->rate_limit_per_minute . " requests/min\n";
    echo "Estado: " . ($client->is_active ? 'Activo' : 'Inactivo') . "\n\n";
    
    echo "⚠️  IMPORTANTE: Guarda estos datos de forma segura.\n";
    echo "   El API Secret solo se muestra una vez.\n\n";
    
    echo "========================================\n";
    echo "  PRUEBA RÁPIDA\n";
    echo "========================================\n\n";
    
    echo "Para obtener un token, ejecuta:\n\n";
    echo "curl -X POST \"http://localhost/api/v1/auth/token\" \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -d '{\n";
    echo "    \"api_key\": \"" . $client->api_key . "\",\n";
    echo "    \"api_secret\": \"" . $client->plain_secret . "\"\n";
    echo "  }'\n\n";
    
    echo "========================================\n\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
