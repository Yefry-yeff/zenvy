<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Log;

class AuditApiRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo auditar si está habilitado
        if (!config('api.audit.enabled')) {
            return $next($request);
        }

        $startTime = microtime(true);
        
        // Continuar con el request
        $response = $next($request);
        
        // Registrar después de la respuesta
        $this->logRequest($request, $response, $startTime);
        
        return $response;
    }
    
    /**
     * Registrar el request en la base de datos
     */
    private function logRequest($request, $response, $startTime): void
    {
        try {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $requestBody = config('api.audit.log_requests') 
                ? $this->sanitizeData($request->all()) 
                : [];

            $responseBody = config('api.audit.log_responses')
                ? $this->sanitizeData(json_decode($response->content(), true))
                : [];
            
            ApiLog::create([
                'client_id' => $request->api_client?->id,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'request_body' => $requestBody,
                'response_status' => $response->status(),
                'response_body' => $responseBody,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $duration,
                'created_at' => now(),
            ]);

            // Log adicional si hay error
            if ($response->status() >= 500) {
                Log::error('API Error 5xx', [
                    'client' => $request->api_client?->name,
                    'endpoint' => $request->path(),
                    'status' => $response->status(),
                    'duration' => $duration,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error logging API request: ' . $e->getMessage());
        }
    }
    
    /**
     * Sanitizar datos sensibles
     */
    private function sanitizeData($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        // Campos sensibles a ocultar
        $sensitive = [
            'password',
            'api_secret',
            'token',
            'credit_card',
            'cvv',
            'card_number',
            'api_key',
        ];
        
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }
        
        return $data;
    }
}
