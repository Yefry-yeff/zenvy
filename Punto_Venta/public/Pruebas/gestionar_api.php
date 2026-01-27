<?php
/**
 * Utilidad de Gestión del API
 * 
 * Herramienta de línea de comandos para gestionar clientes API,
 * ver logs, estadísticas y más.
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║        UTILIDAD DE GESTIÓN DEL API REST POS                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Menú principal
echo "Seleccione una opción:\n\n";
echo "  1. Listar clientes API\n";
echo "  2. Crear nuevo cliente API\n";
echo "  3. Desactivar cliente API\n";
echo "  4. Ver estadísticas de uso\n";
echo "  5. Ver últimos logs\n";
echo "  6. Limpiar logs antiguos\n";
echo "  7. Probar conectividad\n";
echo "  0. Salir\n";
echo "\n";
echo "Opción: ";

$option = trim(fgets(STDIN));

switch ($option) {
    case '1':
        listarClientes();
        break;
    case '2':
        crearCliente();
        break;
    case '3':
        desactivarCliente();
        break;
    case '4':
        verEstadisticas();
        break;
    case '5':
        verLogs();
        break;
    case '6':
        limpiarLogs();
        break;
    case '7':
        probarConectividad();
        break;
    case '0':
        echo "¡Hasta luego!\n\n";
        exit(0);
    default:
        echo "Opción inválida\n";
        exit(1);
}

// ========================================
// FUNCIONES
// ========================================

function listarClientes() {
    echo "\n╔═══ CLIENTES API ═══════════════════════════════════════════╗\n\n";
    
    $clients = DB::table('api_clients')
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($clients->isEmpty()) {
        echo "  No hay clientes registrados.\n\n";
        return;
    }
    
    foreach ($clients as $client) {
        $status = $client->active ? '✓ Activo' : '✗ Inactivo';
        echo "  ID: {$client->id}\n";
        echo "  Nombre: {$client->name}\n";
        echo "  API Key: {$client->api_key}\n";
        echo "  Estado: {$status}\n";
        echo "  Rate Limit: {$client->rate_limit} req/min\n";
        echo "  Creado: {$client->created_at}\n";
        echo "  " . str_repeat("─", 58) . "\n";
    }
    
    echo "\n";
}

function crearCliente() {
    echo "\n╔═══ CREAR NUEVO CLIENTE API ═══════════════════════════════╗\n\n";
    
    echo "Nombre del cliente: ";
    $name = trim(fgets(STDIN));
    
    if (empty($name)) {
        echo "Error: El nombre es requerido\n";
        exit(1);
    }
    
    echo "Rate limit (requests/minuto) [100]: ";
    $rateLimit = trim(fgets(STDIN));
    $rateLimit = empty($rateLimit) ? 100 : (int)$rateLimit;
    
    // Generar credenciales
    $apiKey = 'pk_' . bin2hex(random_bytes(16));
    $apiSecret = 'sk_' . bin2hex(random_bytes(32));
    $hashedSecret = hash('sha256', $apiSecret);
    
    // Insertar en BD
    $clientId = DB::table('api_clients')->insertGetId([
        'name' => $name,
        'api_key' => $apiKey,
        'api_secret' => $hashedSecret,
        'rate_limit' => $rateLimit,
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "\n✓ Cliente creado exitosamente!\n\n";
    echo "  ID: {$clientId}\n";
    echo "  Nombre: {$name}\n";
    echo "  API Key: {$apiKey}\n";
    echo "  API Secret: {$apiSecret}\n";
    echo "  Rate Limit: {$rateLimit} req/min\n";
    echo "\n";
    echo "  ⚠️  IMPORTANTE: Guarda el API Secret, no se puede recuperar.\n\n";
}

function desactivarCliente() {
    echo "\n╔═══ DESACTIVAR CLIENTE API ════════════════════════════════╗\n\n";
    
    listarClientes();
    
    echo "ID del cliente a desactivar: ";
    $clientId = trim(fgets(STDIN));
    
    $updated = DB::table('api_clients')
        ->where('id', $clientId)
        ->update(['active' => false, 'updated_at' => now()]);
    
    if ($updated) {
        echo "\n✓ Cliente desactivado exitosamente\n\n";
    } else {
        echo "\n✗ Error: Cliente no encontrado\n\n";
    }
}

function verEstadisticas() {
    echo "\n╔═══ ESTADÍSTICAS DE USO DEL API ════════════════════════════╗\n\n";
    
    // Total de requests
    $totalRequests = DB::table('api_logs')->count();
    echo "  Total de requests: " . number_format($totalRequests) . "\n";
    
    // Requests por cliente
    echo "\n  Requests por cliente:\n";
    $byClient = DB::table('api_logs')
        ->select('client_name', DB::raw('COUNT(*) as total'))
        ->groupBy('client_name')
        ->orderByDesc('total')
        ->get();
    
    foreach ($byClient as $client) {
        echo "    • {$client->client_name}: " . number_format($client->total) . " requests\n";
    }
    
    // Requests por endpoint
    echo "\n  Endpoints más usados:\n";
    $byEndpoint = DB::table('api_logs')
        ->select('endpoint', DB::raw('COUNT(*) as total'))
        ->groupBy('endpoint')
        ->orderByDesc('total')
        ->limit(10)
        ->get();
    
    foreach ($byEndpoint as $endpoint) {
        echo "    • {$endpoint->endpoint}: " . number_format($endpoint->total) . " requests\n";
    }
    
    // Requests con errores
    $errors = DB::table('api_logs')
        ->where('status_code', '>=', 400)
        ->count();
    $errorRate = $totalRequests > 0 ? ($errors / $totalRequests * 100) : 0;
    
    echo "\n  Requests con errores: " . number_format($errors) . " (" . number_format($errorRate, 2) . "%)\n";
    
    // Tiempo promedio de respuesta
    $avgTime = DB::table('api_logs')->avg('response_time');
    echo "  Tiempo promedio de respuesta: " . number_format($avgTime, 2) . " ms\n";
    
    // Requests hoy
    $today = DB::table('api_logs')
        ->whereDate('created_at', today())
        ->count();
    echo "  Requests hoy: " . number_format($today) . "\n";
    
    echo "\n";
}

function verLogs() {
    echo "\n╔═══ ÚLTIMOS LOGS DEL API ═══════════════════════════════════╗\n\n";
    
    echo "¿Cuántos registros desea ver? [20]: ";
    $limit = trim(fgets(STDIN));
    $limit = empty($limit) ? 20 : (int)$limit;
    
    $logs = DB::table('api_logs')
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    
    foreach ($logs as $log) {
        $statusIcon = $log->status_code < 400 ? '✓' : '✗';
        echo "  {$statusIcon} [{$log->created_at}] {$log->method} {$log->endpoint}\n";
        echo "    Cliente: {$log->client_name} | Status: {$log->status_code} | Tiempo: {$log->response_time}ms\n";
        
        if ($log->status_code >= 400) {
            $response = json_decode($log->response_body, true);
            if (isset($response['error']['message'])) {
                echo "    Error: {$response['error']['message']}\n";
            }
        }
        
        echo "\n";
    }
}

function limpiarLogs() {
    echo "\n╔═══ LIMPIAR LOGS ANTIGUOS ══════════════════════════════════╗\n\n";
    
    echo "¿Eliminar logs más antiguos de cuántos días? [30]: ";
    $days = trim(fgets(STDIN));
    $days = empty($days) ? 30 : (int)$days;
    
    $deleted = DB::table('api_logs')
        ->where('created_at', '<', now()->subDays($days))
        ->delete();
    
    echo "\n✓ Se eliminaron " . number_format($deleted) . " registros de logs\n\n";
}

function probarConectividad() {
    echo "\n╔═══ PROBAR CONECTIVIDAD DEL API ════════════════════════════╗\n\n";
    
    $baseUrl = 'http://127.0.0.1:8000/api';
    
    echo "Probando health check...\n";
    $ch = curl_init($baseUrl . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "  ✓ API funcionando correctamente\n";
        echo "  Version: {$data['version']}\n";
        echo "  Timestamp: {$data['timestamp']}\n";
    } else {
        echo "  ✗ Error: API no responde (HTTP {$httpCode})\n";
    }
    
    echo "\nProbando conexión a base de datos...\n";
    try {
        $count = DB::table('producto')->count();
        echo "  ✓ Base de datos conectada\n";
        echo "  Productos en BD: " . number_format($count) . "\n";
    } catch (\Exception $e) {
        echo "  ✗ Error de conexión: {$e->getMessage()}\n";
    }
    
    echo "\n";
}
