<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaReservaInventario extends Model
{
    protected $table = 'auditoria_reservas_inventario';
    
    public $timestamps = false;
    
    protected $fillable = [
        'reserva_id',
        'pedido_web_id',
        'producto_id',
        'numero_pedido',
        'nombre_producto',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'cantidad_anterior',
        'cantidad_nueva',
        'stock_disponible_antes',
        'stock_disponible_despues',
        'usuario_id',
        'usuario_nombre',
        'motivo',
        'metadata',
        'ip',
        'fecha_accion',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'cantidad_anterior' => 'integer',
        'cantidad_nueva' => 'integer',
        'stock_disponible_antes' => 'integer',
        'stock_disponible_despues' => 'integer',
        'fecha_accion' => 'datetime',
    ];
    
    /**
     * Relación con reserva
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(ReservaInventario::class, 'reserva_id');
    }
    
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
}
