<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'evento',
        'direccion',
        'url',
        'payload',
        'headers',
        'status_code',
        'response',
        'exitoso',
        'intentos',
        'tiempo_respuesta',
        'mensaje_error',
        'relacionado_id',
        'relacionado_tipo',
        'usuario_id',
    ];

    protected $casts = [
        'exitoso' => 'boolean',
        'tiempo_respuesta' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'idusuario');
    }

    /**
     * Scope para logs exitosos
     */
    public function scopeExitosos($query)
    {
        return $query->where('exitoso', true);
    }

    /**
     * Scope para logs fallidos
     */
    public function scopeFallidos($query)
    {
        return $query->where('exitoso', false);
    }

    /**
     * Scope para logs de salida (outgoing)
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direccion', 'outgoing');
    }

    /**
     * Scope para logs de entrada (incoming)
     */
    public function scopeIncoming($query)
    {
        return $query->where('direccion', 'incoming');
    }

    /**
     * Scope para buscar por evento
     */
    public function scopePorEvento($query, $evento)
    {
        return $query->where('evento', $evento);
    }

    /**
     * Scope para logs recientes
     */
    public function scopeRecientes($query, $horas = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($horas));
    }

    /**
     * Obtener estadísticas de webhooks
     */
    public static function estadisticas($dias = 7)
    {
        $fechaInicio = now()->subDays($dias);
        
        return [
            'total' => self::where('created_at', '>=', $fechaInicio)->count(),
            'exitosos' => self::where('created_at', '>=', $fechaInicio)->where('exitoso', true)->count(),
            'fallidos' => self::where('created_at', '>=', $fechaInicio)->where('exitoso', false)->count(),
            'promedio_tiempo' => self::where('created_at', '>=', $fechaInicio)
                                    ->where('exitoso', true)
                                    ->avg('tiempo_respuesta'),
            'ultima_hora' => self::where('created_at', '>=', now()->subHour())->count(),
        ];
    }

    /**
     * Limpiar logs antiguos
     */
    public static function limpiarAntiguos($dias = 30)
    {
        return self::where('created_at', '<', now()->subDays($dias))->delete();
    }
}
