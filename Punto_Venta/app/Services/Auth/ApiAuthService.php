<?php

namespace App\Services\Auth;

use App\Models\ApiClient;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;

class ApiAuthService
{
    /**
     * Generar token JWT para un cliente
     */
    public function generateToken(string $apiKey, string $apiSecret): array
    {
        $client = ApiClient::where('api_key', $apiKey)->first();

        if (!$client || !$client->is_active) {
            throw new \Exception('Credenciales inválidas o cliente inactivo');
        }

        // Verificar el secret (debe estar hasheado en la BD)
        if (!Hash::check($apiSecret, $client->api_secret)) {
            throw new \Exception('Credenciales inválidas');
        }

        $payload = [
            'iss' => config('app.url'),
            'sub' => 'api_access',
            'client_id' => $client->id,
            'client_name' => $client->name,
            'iat' => time(),
            'exp' => time() + config('api.auth.token_ttl'),
        ];

        $token = JWT::encode($payload, config('api.auth.jwt_secret'), 'HS256');

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('api.auth.token_ttl'),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
            ]
        ];
    }

    /**
     * Generar un nuevo cliente API
     */
    public function createClient(string $name, array $options = []): ApiClient
    {
        $apiKey = ApiClient::generateApiKey();
        $apiSecret = ApiClient::generateApiSecret();

        $client = ApiClient::create([
            'name' => $name,
            'api_key' => $apiKey,
            'api_secret' => Hash::make($apiSecret), // Guardar hasheado
            'is_active' => $options['is_active'] ?? true,
            'ip_whitelist' => $options['ip_whitelist'] ?? null,
            'rate_limit_per_minute' => $options['rate_limit_per_minute'] ?? config('api.rate_limit.max_attempts'),
        ]);

        // Retornar el secret sin hashear SOLO en la creación
        $client->plain_secret = $apiSecret;

        return $client;
    }

    /**
     * Rotar API Secret de un cliente
     */
    public function rotateSecret(int $clientId): array
    {
        $client = ApiClient::findOrFail($clientId);
        
        $newSecret = ApiClient::generateApiSecret();
        
        $client->update([
            'api_secret' => Hash::make($newSecret)
        ]);

        return [
            'client_id' => $client->id,
            'api_key' => $client->api_key,
            'api_secret' => $newSecret, // Mostrar solo una vez
            'message' => 'Secret rotado exitosamente. Guarde el nuevo secret de forma segura.'
        ];
    }

    /**
     * Desactivar un cliente
     */
    public function deactivateClient(int $clientId): bool
    {
        return ApiClient::where('id', $clientId)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * Activar un cliente
     */
    public function activateClient(int $clientId): bool
    {
        return ApiClient::where('id', $clientId)
            ->update(['is_active' => true]) > 0;
    }
}
