<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'method',
        'endpoint',
        'request_body',
        'response_status',
        'response_body',
        'ip_address',
        'user_agent',
        'duration_ms',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
        'response_status' => 'integer',
        'duration_ms' => 'float',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Relación con el cliente del API
     */
    public function client()
    {
        return $this->belongsTo(ApiClient::class, 'client_id');
    }

    /**
     * Scope para logs de un cliente específico
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope para logs de errores
     */
    public function scopeErrors($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Scope para logs de un rango de fechas
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
