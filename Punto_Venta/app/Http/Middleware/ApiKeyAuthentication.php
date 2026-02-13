<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;

class ApiKeyAuthentication
{
    /**
     * Middleware para autenticación mediante API Key
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener API key del header
        $apiKey = $request->header('X-API-Key') ?? $request->bearerToken();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'API_KEY_MISSING',
                    'message' => 'API Key requerida. Use header X-API-Key o Authorization Bearer'
                ]
            ], 401);
        }

        // Verificar la API key
        $apiKeyModel = ApiKey::verificar($apiKey);

        if (!$apiKeyModel) {
            Log::warning('Intento de acceso con API Key inválida', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'API_KEY_INVALID',
                    'message' => 'API Key inválida o expirada'
                ]
            ], 401);
        }

        // Verificar IP si está configurada
        if (!$apiKeyModel->ipPermitida($request->ip())) {
            Log::warning('Acceso denegado por restricción de IP', [
                'api_key_id' => $apiKeyModel->id,
                'ip' => $request->ip(),
                'ip_permitidas' => $apiKeyModel->ip_permitidas,
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'IP_NOT_ALLOWED',
                    'message' => 'Acceso denegado desde esta IP'
                ]
            ], 403);
        }

        // Adjuntar la API key al request para uso posterior
        $request->attributes->set('api_key', $apiKeyModel);

        return $next($request);
    }
}
