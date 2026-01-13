<?php
/**
 * Script para crear/obtener credenciales de API Client
 */

require __DIR__ . '/Punto_Venta/vendor/autoload.php';
$app = require_once __DIR__ . '/Punto_Venta/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Auth\ApiAuthService;
use Illuminate\Support\Facades\DB;

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        CREDENCIALES API - INSOMNIA                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

try {
    $service = new ApiAuthService();
    
    // Opción: Crear nuevo cliente
    echo "📋 Opciones:\n";
    echo "1. Crear NUEVO cliente API\n";
    echo "2. Rotar secret del cliente existente (ID: 1)\n";
    echo "3. Mostrar info del cliente existente\n\n";
    
    echo "Selecciona opción (1-3): ";
    $option = trim(fgets(STDIN));
    
    echo "\n";
    
    switch($option) {
        case '1':
            echo "🔨 Creando nuevo cliente API...\n\n";
            $client = $service->createClient('Insomnia Test Client', [
                'is_active' => true,
                'rate_limit_per_minute' => 60
            ]);
            
            echo "✅ Cliente creado exitosamente!\n\n";
            echo "╔═══════════════════════════════════════════════════════════════╗\n";
            echo "║  GUARDA ESTOS VALORES - NO SE PUEDEN RECUPERAR DESPUÉS       ║\n";
            echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
            echo "📋 ID: {$client->id}\n";
            echo "📛 Nombre: {$client->name}\n";
            echo "🔑 API Key:\n   {$client->api_key}\n\n";
            echo "🔐 API Secret (SIN HASHEAR - USA ESTE):\n   {$client->plain_secret}\n\n";
            
            echo "📝 Configuración para Insomnia Environment:\n";
            echo "{\n";
            echo "  \"api_key\": \"{$client->api_key}\",\n";
            echo "  \"api_secret\": \"{$client->plain_secret}\"\n";
            echo "}\n\n";
            break;
            
        case '2':
            echo "🔄 Rotando secret del cliente ID: 1...\n\n";
            $result = $service->rotateSecret(1);
            
            echo "✅ Secret rotado exitosamente!\n\n";
            echo "╔═══════════════════════════════════════════════════════════════╗\n";
            echo "║  NUEVO SECRET - GUÁRDALO                                      ║\n";
            echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
            echo "🔐 Nuevo API Secret:\n   {$result['new_secret']}\n\n";
            
            // Obtener el cliente
            $client = DB::table('api_clients')->find(1);
            echo "🔑 API Key (sin cambios):\n   {$client->api_key}\n\n";
            
            echo "📝 Configuración para Insomnia Environment:\n";
            echo "{\n";
            echo "  \"api_key\": \"{$client->api_key}\",\n";
            echo "  \"api_secret\": \"{$result['new_secret']}\"\n";
            echo "}\n\n";
            break;
            
        case '3':
            echo "ℹ️  Mostrando información del cliente existente...\n\n";
            $client = DB::table('api_clients')->where('id', 1)->first();
            
            if (!$client) {
                echo "❌ No se encontró el cliente ID: 1\n";
                echo "   Usa la opción 1 para crear uno nuevo.\n";
                exit(1);
            }
            
            echo "📋 ID: {$client->id}\n";
            echo "📛 Nombre: {$client->name}\n";
            echo "🔑 API Key:\n   {$client->api_key}\n\n";
            echo "⚠️  API Secret: El secret está hasheado en la BD.\n";
            echo "   No se puede recuperar el valor original.\n\n";
            echo "💡 Opciones:\n";
            echo "   - Usa la opción 2 para generar un nuevo secret\n";
            echo "   - O usa la opción 1 para crear un cliente completamente nuevo\n\n";
            
            echo "🔑 API Key para copiar:\n";
            echo "{$client->api_key}\n\n";
            break;
            
        default:
            echo "❌ Opción inválida\n";
            exit(1);
    }
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  PRÓXIMOS PASOS                                               ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "1. Copia el API Key y API Secret\n";
    echo "2. Abre Insomnia\n";
    echo "3. Importa: Insomnia_Zenvy_API_Ventas.json\n";
    echo "4. Configura el Environment con estos valores\n";
    echo "5. Ejecuta el request '1. Obtener Token JWT'\n";
    echo "6. Copia el token en el Environment\n";
    echo "7. ¡Listo para probar las ventas!\n\n";
    
    echo "📖 Lee GUIA_INSOMNIA_CONFIGURACION.md para instrucciones detalladas.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "✅ Proceso completado.\n";
