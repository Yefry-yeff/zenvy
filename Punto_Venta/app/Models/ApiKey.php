<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'key',
        'activo',
        'ultimo_uso',
        'total_requests',
        'permisos',
        'ip_permitidas',
        'expira_en',
        'usuario_id',
        'descripcion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_uso' => 'datetime',
        'expira_en' => 'datetime',
        'permisos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'key', // No exponer la key en respuestas JSON por defecto
    ];

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'idusuario');
    }

    /**
     * Generar una nueva API key
     */
    public static function generar($nombre, $usuarioId = null, $permisos = [], $descripcion = null)
    {
        $key = 'zenvy_' . Str::random(40);
        
        return self::create([
            'nombre' => $nombre,
            'key' => hash('sha256', $key), // Guardar hash de la key
            'activo' => true,
            'permisos' => $permisos,
            'usuario_id' => $usuarioId,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Verificar si la API key es válida
     */
    public static function verificar($key)
    {
        $hashedKey = hash('sha256', $key);
        
        $apiKey = self::where('key', $hashedKey)
                      ->where('activo', true)
                      ->first();
        
        if (!$apiKey) {
            return false;
        }

        // Verificar si está expirada
        if ($apiKey->expira_en && $apiKey->expira_en->isPast()) {
            return false;
        }

        // Actualizar último uso y contador
        $apiKey->update([
            'ultimo_uso' => now(),
            'total_requests' => $apiKey->total_requests + 1,
        ]);

        return $apiKey;
    }

    /**
     * Verificar si tiene un permiso específico
     */
    public function tienePermiso($permiso)
    {
        if (!$this->permisos) {
            return false;
        }

        return in_array($permiso, $this->permisos) || in_array('*', $this->permisos);
    }

    /**
     * Verificar si la IP está permitida
     */
    public function ipPermitida($ip)
    {
        if (!$this->ip_permitidas) {
            return true; // Si no hay restricción, permitir todas
        }

        $ipsPermitidas = explode(',', $this->ip_permitidas);
        return in_array($ip, array_map('trim', $ipsPermitidas));
    }

    /**
     * Scope para keys activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                     ->where(function($q) {
                         $q->whereNull('expira_en')
                           ->orWhere('expira_en', '>', now());
                     });
    }

    /**
     * Scope para keys expiradas
     */
    public function scopeExpiradas($query)
    {
        return $query->where('expira_en', '<=', now());
    }

    /**
     * Desactivar la API key
     */
    public function desactivar()
    {
        return $this->update(['activo' => false]);
    }

    /**
     * Activar la API key
     */
    public function activar()
    {
        return $this->update(['activo' => true]);
    }

    /**
     * Regenerar la API key
     */
    public function regenerar()
    {
        $newKey = 'zenvy_' . Str::random(40);
        $this->update(['key' => hash('sha256', $newKey)]);
        
        return $newKey; // Retornar la key sin hashear (solo esta vez)
    }
}
