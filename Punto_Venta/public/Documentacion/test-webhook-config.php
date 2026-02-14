<?php
/**
 * Script de Diagnóstico de Webhooks
 * Verifica configuración y prueba el envío de webhooks
 */

echo "=================================================\n";
echo "🔍 DIAGNÓSTICO DE WEBHOOKS - INVENTARIO\n";
echo "=================================================\n\n";

// Cargar Laravel
require __DIR__ . '/Punto_Venta/vendor/autoload.php';

$app = require_once __DIR__ . '/Punto_Venta/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar configuración
echo "1️⃣ Verificando Configuración\n";
echo "-------------------------------------------\n";

$webhookUrl = config('app.webhook_url');
$webhookToken = config('app.webhook_token');

echo "WEBHOOK_URL: ";
if (empty($webhookUrl)) {
    echo "❌ NO CONFIGURADA\n";
    echo "   ⚠️ Debes agregar en tu archivo .env:\n";
    echo "   WEBHOOK_URL=https://tu-sitio-web.com/api/webhooks/inventory\n\n";
} else {
    echo "✅ {$webhookUrl}\n";
}

echo "WEBHOOK_TOKEN: ";
if (empty($webhookToken)) {
    echo "❌ NO CONFIGURADO\n";
    echo "   ⚠️ Debes agregar en tu archivo .env:\n";
    echo "   WEBHOOK_TOKEN=tu_token_secreto_aqui\n\n";
} else {
    echo "✅ Configurado (oculto por seguridad)\n";
}

echo "\n";

// Si no está configurado, salir
if (empty($webhookUrl) || empty($webhookToken)) {
    echo "❌ CONFIGURACIÓN INCOMPLETA\n";
    echo "Por favor configura WEBHOOK_URL y WEBHOOK_TOKEN en tu archivo .env\n";
    echo "Ubicación: " . base_path('.env') . "\n\n";
    
    echo "📝 Ejemplo de configuración:\n";
    echo "-------------------------------------------\n";
    echo "WEBHOOK_URL=https://tu-ecommerce.com/api/webhooks/inventory\n";
    echo "WEBHOOK_TOKEN=mi_token_super_secreto_12345\n\n";
    exit(1);
}

// Verificar conectividad
echo "2️⃣ Verificando Conectividad\n";
echo "-------------------------------------------\n";

$parsedUrl = parse_url($webhookUrl);
$host = $parsedUrl['host'];
$port = $parsedUrl['port'] ?? ($parsedUrl['scheme'] === 'https' ? 443 : 80);

echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Probando conexión... ";

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    fclose($connection);
    echo "✅ Conexión exitosa\n";
} else {
    echo "❌ No se pudo conectar\n";
    echo "Error: [{$errno}] {$errstr}\n";
    echo "⚠️ Verifica que la URL sea accesible desde este servidor\n\n";
}

echo "\n";

// Probar envío de webhook
echo "3️⃣ Probando Envío de Webhook\n";
echo "-------------------------------------------\n";

$syncService = app(\App\Services\WebInventorySyncService::class);

echo "Enviando webhook de prueba...\n\n";

$resultado = $syncService->sincronizarCambioStock(
    1,                    // ID de producto de prueba
    'Producto de Prueba',
    10,                   // Stock anterior
    8,                    // Stock actual
    'test',               // Razón
    [
        'tipo_prueba' => 'diagnostico',
        'timestamp' => now()->toIso8601String(),
        'mensaje' => 'Este es un webhook de prueba del sistema de diagnóstico'
    ]
);

if ($resultado) {
    echo "✅ Webhook enviado exitosamente\n";
    echo "\n";
    echo "📋 Verifica en tu aplicación web que haya recibido:\n";
    echo "   - Evento: inventario.stock_actualizado\n";
    echo "   - Producto ID: 1\n";
    echo "   - Producto: Producto de Prueba\n";
    echo "   - Stock anterior: 10\n";
    echo "   - Stock actual: 8\n";
    echo "   - Razón: test\n";
} else {
    echo "❌ Error al enviar webhook\n";
    echo "Revisa los logs en: storage/logs/laravel.log\n";
}

echo "\n";

// Revisar últimos logs
echo "4️⃣ Últimos Logs de Webhooks\n";
echo "-------------------------------------------\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file($logFile);
    $webhookLogs = array_filter($logs, function($line) {
        return strpos($line, 'WEBHOOK') !== false || 
               strpos($line, 'webhook') !== false;
    });
    
    $ultimosLogs = array_slice($webhookLogs, -10);
    
    if (count($ultimosLogs) > 0) {
        echo "Últimas 10 entradas relacionadas con webhooks:\n\n";
        foreach ($ultimosLogs as $log) {
            echo $log;
        }
    } else {
        echo "No se encontraron logs de webhooks recientes\n";
    }
} else {
    echo "❌ Archivo de log no encontrado: {$logFile}\n";
}

echo "\n";
echo "=================================================\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo "=================================================\n";

echo "\n📌 Comandos útiles:\n";
echo "-------------------------------------------\n";
echo "• Probar webhook de stock:     php artisan webhook:test-inventory stock\n";
echo "• Probar webhook de compra:    php artisan webhook:test-inventory compra\n";
echo "• Probar webhook de venta:     php artisan webhook:test-inventory venta\n";
echo "• Ver logs en tiempo real:     tail -f storage/logs/laravel.log | grep WEBHOOK\n";
echo "\n";
