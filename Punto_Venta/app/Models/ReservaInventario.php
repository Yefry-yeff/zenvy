<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservaInventario extends Model
{
    protected $table = 'reservas_inventario';
    
    protected $fillable = [
        'pedido_web_id',
        'producto_id',
        'cantidad_reservada',
        'estado',
        'motivo_liberacion',
        'fecha_liberacion',
        'fecha_consumo',
        'liberado_por',
        'consumido_por',
    ];
    
    protected $casts = [
        'cantidad_reservada' => 'integer',
        'fecha_liberacion' => 'datetime',
        'fecha_consumo' => 'datetime',
    ];
    
    /**
     * Relación con pedido web
     */
    public function pedidoWeb(): BelongsTo
    {
        return $this->belongsTo(PedidoWeb::class, 'pedido_web_id');
    }
    
    /**
     * Relación con producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    
    /**
     * Relación con auditorías
     */
    public function auditorias(): HasMany
    {
        return $this->hasMany(AuditoriaReservaInventario::class, 'reserva_id');
    }
    
    /**
     * Usuario que liberó
     */
    public function liberadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liberado_por');
    }
    
    /**
     * Usuario que consumió
     */
    public function consumidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumido_por');
    }
    
    /**
     * Scope para reservas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }
    
    /**
     * Scope para reservas de un producto específico
     */
    public function scopePorProducto($query, int $productoId)
    {
        return $query->where('producto_id', $productoId);
    }
    
    /**
     * Obtener cantidad total reservada activa de un producto
     */
    public static function cantidadReservadaProducto(int $productoId): int
    {
        return self::where('producto_id', $productoId)
            ->where('estado', 'activa')
            ->sum('cantidad_reservada');
    }
}
