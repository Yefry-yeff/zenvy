<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribucionStock extends Model
{
    use HasFactory;

    protected $table = 'distribucion_stock';
    
    // Disable timestamps since table doesn't have created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'cantidad_distribuida',
        'precio_unitario',
        'fecha_distribucion',
        'comentario',
        'recibido_bodega_id'
    ];

    protected $casts = [
        'fecha_distribucion' => 'date',
        'precio_unitario' => 'decimal:2',
        'cantidad_distribuida' => 'integer'
    ];

    // Relationships
    public function recibidoBodega()
    {
        return $this->belongsTo(RecibidoBodega::class, 'recibido_bodega_id');
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
