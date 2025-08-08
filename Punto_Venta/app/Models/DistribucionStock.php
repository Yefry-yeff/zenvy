<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribucionStock extends Model
{
    use HasFactory;

    protected $table = 'distribucion_stock';
    
    // Enable timestamps for created_at tracking
    public $timestamps = true;
    
    // Only use created_at, disable updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'cantidad_distribuida',
        'precio_unitario',
        'fecha_distribucion',
        'comentario',
        'recibido_bodega_id',
        'users_id',
        'Unidad_medida',
        'traslado_a',
        'estado'
    ];

    protected $casts = [
        'fecha_distribucion' => 'date',
        'precio_unitario' => 'decimal:2',
        'cantidad_distribuida' => 'integer',
        'created_at' => 'datetime'
    ];

    // Relationships
    public function recibidoBodega()
    {
        return $this->belongsTo(RecibidoBodega::class, 'recibido_bodega_id');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }

    // Scopes
    public function scopePorRecibido($query, $recibidoId)
    {
        return $query->where('recibido_bodega_id', $recibidoId);
    }

    // Accessors
    public function getPrecioTotalAttribute()
    {
        return $this->cantidad_distribuida * $this->precio_unitario;
    }
}
