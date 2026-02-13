<?php

namespace App\Livewire\Configuracion;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\WebInventorySyncService;
use App\Models\ApiLog;
use App\Models\ApiClient;

#[Title('Configuración de APIs y Webhooks')]
#[Layout('layouts.app')]
class Api extends Component
{
    // Configuración de Webhooks
    public $webhook_url = '';
    public $webhook_token = '';
    public $webhook_timeout = 5;
    public $webhook_enabled = true;
    public $webhook_retry_attempts = 3;
    
    // Estado de prueba
    public $testando = false;
    public $resultadoPrueba = null;
    public $tipoPrueba = 'stock';
    
    // Logs de webhooks
    public $logs = [];
    public $mostrarLogs = false;
    public $filtroLogs = 'todos'; // todos, exitoso, error
    
    // Estadísticas
    public $estadisticas = [];
    
    // Configuración de API Clients
    public $api_clients = [];
    public $nuevo_client_nombre = '';
    public $generando_client = false;
    public $client_generado = null;
    
    // Tab activo
    public $tabActivo = 'webhooks';
    
    // Monitoreo
    public $estadoConexion = null;
    public $ultimoEnvio = null;

    public function mount()
    {
        $this->cargarConfiguracion();
        $this->cargarEstadisticas();
        $this->cargarApiClients();
        
        // Intentar cargar logs inicialmente
        try {
            $this->cargarLogs();
        } catch (\Exception $e) {
            // Silenciar error si la tabla no existe aún
            Log::debug('No se pudieron cargar logs en mount', ['error' => $e->getMessage()]);
        }
    }

    public function cargarConfiguracion()
    {
        // Cargar configuración desde .env o base de datos
        $this->webhook_url = config('app.webhook_url', env('WEBHOOK_URL', ''));
        $this->webhook_token = config('app.webhook_token', env('WEBHOOK_TOKEN', ''));
        $this->webhook_timeout = config('app.webhook_timeout', 5);
        $this->webhook_enabled = config('app.webhook_enabled', true);
        $this->webhook_retry_attempts = config('app.webhook_retry_attempts', 3);
        
        // Verificar estado de conexión
        $this->verificarEstadoConexion();
        
        // Cargar última actividad
        $this->cargarUltimaActividad();
    }

    public function verificarEstadoConexion()
    {
        if (empty($this->webhook_url)) {
            $this->estadoConexion = [
                'estado' => 'no_configurado',
                'mensaje' => 'Webhook no configurado',
                'color' => 'gray'
            ];
            return;
        }

        try {
            $response = Http::timeout(3)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->webhook_token])
                ->get($this->webhook_url . '/health');
            
            if ($response->successful()) {
                $this->estadoConexion = [
                    'estado' => 'conectado',
                    'mensaje' => 'Conexión exitosa',
                    'color' => 'green'
                ];
            } else {
                $this->estadoConexion = [
                    'estado' => 'error',
                    'mensaje' => 'Error de conexión: ' . $response->status(),
                    'color' => 'red'
                ];
            }
        } catch (\Exception $e) {
            $this->estadoConexion = [
                'estado' => 'error',
                'mensaje' => 'No se puede conectar',
                'color' => 'yellow'
            ];
        }
    }

    public function cargarUltimaActividad()
    {
        // Intentar cargar desde cache o base de datos
        $this->ultimoEnvio = Cache::get('webhook_ultimo_envio', [
            'fecha' => null,
            'evento' => null,
            'estado' => null
        ]);
    }

    public function guardarConfiguracion()
    {
        $this->validate([
            'webhook_url' => 'nullable|url',
            'webhook_token' => 'nullable|string|min:10',
            'webhook_timeout' => 'required|integer|min:1|max:30',
            'webhook_retry_attempts' => 'required|integer|min:1|max:5',
        ], [
            'webhook_url.url' => 'La URL del webhook debe ser válida',
            'webhook_token.min' => 'El token debe tener al menos 10 caracteres',
            'webhook_timeout.min' => 'El timeout debe ser al menos 1 segundo',
            'webhook_timeout.max' => 'El timeout no puede exceder 30 segundos',
        ]);

        try {
            // Actualizar archivo .env
            $this->actualizarEnv([
                'WEBHOOK_URL' => $this->webhook_url,
                'WEBHOOK_TOKEN' => $this->webhook_token,
                'WEBHOOK_TIMEOUT' => $this->webhook_timeout,
                'WEBHOOK_ENABLED' => $this->webhook_enabled ? 'true' : 'false',
                'WEBHOOK_RETRY_ATTEMPTS' => $this->webhook_retry_attempts,
            ]);

            // Limpiar cache de configuración
            Artisan::call('config:clear');
            
            // Recargar configuración
            $this->cargarConfiguracion();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '✅ Configuración guardada exitosamente'
            ]);
            
            Log::info('Configuración de webhooks actualizada', [
                'usuario' => auth()->user()->name ?? 'Sistema',
                'webhook_url' => $this->webhook_url
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al guardar configuración: ' . $e->getMessage()
            ]);
            
            Log::error('Error al guardar configuración de webhooks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function probarWebhook()
    {
        $this->testando = true;
        $this->resultadoPrueba = null;

        try {
            $syncService = app(WebInventorySyncService::class);
            
            $payload = [
                'evento' => 'test.conexion',
                'timestamp' => now()->toIso8601String(),
                'mensaje' => 'Prueba de conexión desde Zenvy POS',
                'tipo' => $this->tipoPrueba,
                'datos_prueba' => [
                    'producto_id' => 1,
                    'nombre' => 'Producto de Prueba',
                    'stock' => 100,
                ]
            ];

            $response = Http::timeout($this->webhook_timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->webhook_token,
                    'Content-Type' => 'application/json',
                    'X-Webhook-Test' => 'true'
                ])
                ->post($this->webhook_url, $payload);

            if ($response->successful()) {
                $this->resultadoPrueba = [
                    'success' => true,
                    'status' => $response->status(),
                    'mensaje' => '✅ Webhook funcionando correctamente',
                    'respuesta' => $response->json(),
                    'tiempo' => $response->transferStats ? $response->transferStats->getTransferTime() : null
                ];
            } else {
                $this->resultadoPrueba = [
                    'success' => false,
                    'status' => $response->status(),
                    'mensaje' => '⚠️ El webhook respondió con error',
                    'respuesta' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            $this->resultadoPrueba = [
                'success' => false,
                'mensaje' => '❌ Error al conectar con el webhook',
                'error' => $e->getMessage()
            ];
        }

        $this->testando = false;
    }

    public function cargarLogs()
    {
        $this->mostrarLogs = true;
        
        try {
            // Verificar si la tabla existe
            if (!\Illuminate\Support\Facades\Schema::hasTable('api_logs')) {
                $this->logs = [];
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => '⚠️ Tabla api_logs no existe. Ejecuta: php artisan migrate'
                ]);
                return;
            }
            
            // Cargar logs desde la base de datos
            $logsQuery = ApiLog::with('client')->orderBy('created_at', 'desc')->limit(100);
            
            // Aplicar filtro si está seleccionado
            if ($this->filtroLogs === 'exitoso') {
                $logsQuery->where('response_status', '<', 400);
            } elseif ($this->filtroLogs === 'error') {
                $logsQuery->where('response_status', '>=', 400);
            }
            
            $logsDB = $logsQuery->get();
            
            $this->logs = $logsDB->map(function($log) {
                return [
                    'fecha' => $log->created_at->format('Y-m-d H:i:s'),
                    'mensaje' => "{$log->method} {$log->endpoint} - Status: {$log->response_status}",
                    'tipo' => $log->response_status < 400 ? 'success' : 'error',
                    'detalles' => [
                        'client' => $log->client ? $log->client->name : 'Desconocido',
                        'ip' => $log->ip_address,
                        'duration_ms' => $log->duration_ms ? round($log->duration_ms, 2) . 'ms' : 'N/A',
                        'user_agent' => $log->user_agent,
                        'request' => is_array($log->request_body) ? json_encode($log->request_body, JSON_PRETTY_PRINT) : $log->request_body,
                        'response' => is_array($log->response_body) ? json_encode($log->response_body, JSON_PRETTY_PRINT) : $log->response_body,
                    ]
                ];
            })->toArray();
            
            Log::info('📋 API Logs cargados', ['total' => count($this->logs)]);
            
        } catch (\Exception $e) {
            $this->logs = [];
            Log::error('Error al cargar logs de API desde BD', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al cargar logs: ' . $e->getMessage()
            ]);
        }
    }

    public function cargarEstadisticas()
    {
        try {
            // Calcular estadísticas desde api_logs
            $ultimos7Dias = now()->subDays(7);
            
            $total = ApiLog::where('created_at', '>=', $ultimos7Dias)->count();
            $exitosos = ApiLog::where('created_at', '>=', $ultimos7Dias)
                ->where('response_status', '<', 400)
                ->count();
            $fallidos = ApiLog::where('created_at', '>=', $ultimos7Dias)
                ->where('response_status', '>=', 400)
                ->count();
            $ultimaHora = ApiLog::where('created_at', '>=', now()->subHour())->count();
            $promedioTiempo = ApiLog::where('created_at', '>=', $ultimos7Dias)
                ->whereNotNull('duration_ms')
                ->avg('duration_ms');
            
            $this->estadisticas = [
                'total_enviados' => $total,
                'exitosos' => $exitosos,
                'fallidos' => $fallidos,
                'ultima_hora' => $ultimaHora,
                'promedio_tiempo' => $promedioTiempo ? round($promedioTiempo, 2) : 0,
            ];
        } catch (\Exception $e) {
            // Fallback a valores por defecto
            $this->estadisticas = [
                'total_enviados' => 0,
                'exitosos' => 0,
                'fallidos' => 0,
                'ultima_hora' => 0,
                'promedio_tiempo' => 0,
            ];
            
            Log::error('Error al cargar estadísticas de API', ['error' => $e->getMessage()]);
        }
    }

    public function limpiarLogs()
    {
        try {
            // Limpiar logs de la base de datos
            $eliminados = ApiLog::where('created_at', '<', now()->subDays(30))->delete();
            
            $this->logs = [];
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "✅ {$eliminados} logs eliminados correctamente"
            ]);
            
            // Recargar estadísticas
            $this->cargarEstadisticas();
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al limpiar logs: ' . $e->getMessage()
            ]);
        }
    }

    public function generarToken()
    {
        $token = base64_encode('Zenvy-POS-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(16)));
        $this->webhook_token = $token;
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => '✅ Token generado correctamente'
        ]);
    }

    public function cambiarTab($tab)
    {
        $this->tabActivo = $tab;
        
        // Recargar datos según el tab
        if ($tab === 'logs') {
            $this->cargarLogs();
        } elseif ($tab === 'api-keys') {
            $this->cargarApiClients();
        } elseif ($tab === 'estadisticas') {
            $this->cargarEstadisticas();
        }
    }

    public function cargarApiClients()
    {
        try {
            // Verificar si la tabla existe
            if (!\Illuminate\Support\Facades\Schema::hasTable('api_clients')) {
                $this->api_clients = [];
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => '⚠️ Tabla api_clients no existe. Ejecuta: php artisan migrate'
                ]);
                return;
            }
            
            $this->api_clients = ApiClient::with('logs')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'api_key' => $client->api_key,
                        'key_preview' => substr($client->api_key, 0, 15) . '...',
                        'is_active' => $client->is_active,
                        'rate_limit' => $client->rate_limit_per_minute,
                        'total_requests' => $client->logs()->count(),
                        'ip_whitelist' => $client->ip_whitelist ? implode(', ', $client->ip_whitelist) : 'Todas las IPs',
                        'created_at' => $client->created_at->format('Y-m-d H:i:s'),
                    ];
                })
                ->toArray();
                
            Log::info('🔑 API Clients cargados', ['total' => count($this->api_clients)]);
            
        } catch (\Exception $e) {
            $this->api_clients = [];
            Log::error('Error al cargar API clients', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al cargar API Clients: ' . $e->getMessage()
            ]);
        }
    }

    public function crearApiClient()
    {
        $this->validate([
            'nuevo_client_nombre' => 'required|string|min:3|max:100',
        ], [
            'nuevo_client_nombre.required' => 'El nombre es requerido',
            'nuevo_client_nombre.min' => 'El nombre debe tener al menos 3 caracteres',
        ]);

        try {
            $this->generando_client = true;
            
            // Generar nuevas credenciales
            $apiKey = ApiClient::generateApiKey();
            $apiSecret = ApiClient::generateApiSecret();
            
            $client = ApiClient::create([
                'name' => $this->nuevo_client_nombre,
                'api_key' => $apiKey,
                'api_secret' => hash('sha256', $apiSecret),
                'is_active' => true,
                'ip_whitelist' => null,
                'rate_limit_per_minute' => 60,
            ]);

            // Almacenar datos temporales para mostrar
            $this->client_generado = [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'name' => $this->nuevo_client_nombre
            ];

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '✅ API Client creado exitosamente'
            ]);

            $this->nuevo_client_nombre = '';
            $this->generando_client = false;
            
            // Recargar lista
            $this->cargarApiClients();
            
            Log::info('API Client creado', [
                'nombre' => $client->name,
                'usuario' => auth()->user()->name ?? 'Sistema'
            ]);
            
        } catch (\Exception $e) {
            $this->generando_client = false;
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al crear API Client: ' . $e->getMessage()
            ]);
            
            Log::error('Error al crear API Client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function toggleApiClient($id)
    {
        try {
            $client = ApiClient::findOrFail($id);
            $client->is_active = !$client->is_active;
            $client->save();

            $estado = $client->is_active ? 'activado' : 'desactivado';
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "✅ API Client {$estado} correctamente"
            ]);

            $this->cargarApiClients();
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al cambiar estado de API Client'
            ]);
        }
    }

    public function eliminarApiClient($id)
    {
        try {
            $client = ApiClient::findOrFail($id);
            $nombre = $client->name;
            $client->delete();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "✅ API Client '{$nombre}' eliminado correctamente"
            ]);

            $this->cargarApiClients();
            
            Log::info('API Client eliminado', [
                'nombre' => $nombre,
                'usuario' => auth()->user()->name ?? 'Sistema'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => '❌ Error al eliminar API Client'
            ]);
        }
    }

    private function actualizarEnv(array $datos)
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            throw new \Exception('Archivo .env no encontrado');
        }

        $envContent = file_get_contents($envPath);
        
        foreach ($datos as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }
        
        file_put_contents($envPath, $envContent);
    }

    public function render()
    {
        return view('livewire.configuracion.api', [
            'estadoConexion' => $this->estadoConexion,
            'ultimoEnvio' => $this->ultimoEnvio,
        ]);
    }
}
