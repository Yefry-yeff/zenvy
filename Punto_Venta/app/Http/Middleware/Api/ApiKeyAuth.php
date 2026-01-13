<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiClient;
use Illuminate\Support\Facades\Cache;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return $this->unauthorizedResponse('Token de autenticación no proporcionado');
        }

        try {
            // Verificar token JWT
            $payload = $this->verifyJWT($token);
            
            // Cachear cliente para evitar consultas repetidas
            $client = Cache::remember(
                "api_client_{$payload->client_id}",
                300,
                fn() => ApiClient::find($payload->client_id)
            );
            
            if (!$client || !$client->is_active) {
                throw new \Exception('Cliente inactivo o no encontrado');
            }

            // Verificar IP whitelist
            if (!$client->isIpAllowed($request->ip())) {
                throw new \Exception('IP no autorizada');
            }
            
            // Inyectar cliente en el request
            $request->merge(['api_client' => $client]);
            
            return $next($request);
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return $this->unauthorizedResponse('Token expirado');
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return $this->unauthorizedResponse('Firma de token inválida');
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Token inválido: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar y decodificar el JWT
     */
    private function verifyJWT(string $token): object
    {
        $secret = config('api.auth.jwt_secret');
        
        return JWT::decode(
            $token,
            new Key($secret, 'HS256')
        );
    }

    /**
     * Respuesta de no autorizado
     */
    private function unauthorizedResponse(string $message)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message
            ]
        ], 401);
    }
}
