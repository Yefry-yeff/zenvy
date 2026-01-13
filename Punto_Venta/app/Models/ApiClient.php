<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'api_secret',
        'is_active',
        'ip_whitelist',
        'rate_limit_per_minute',
    ];

    protected $hidden = [
        'api_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ip_whitelist' => 'array',
        'rate_limit_per_minute' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con logs del API
     */
    public function logs()
    {
        return $this->hasMany(ApiLog::class, 'client_id');
    }

    /**
     * Generar un nuevo API Key
     */
    public static function generateApiKey(): string
    {
        return 'pk_' . Str::random(32);
    }

    /**
     * Generar un nuevo API Secret
     */
    public static function generateApiSecret(): string
    {
        return 'sk_' . Str::random(64);
    }

    /**
     * Verificar si una IP está en la whitelist
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true; // Sin restricción
        }

        return in_array($ip, $this->ip_whitelist);
    }

    /**
     * Verificar API Secret
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->api_secret, hash('sha256', $secret));
    }
}
