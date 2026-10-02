<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoWebItem extends Model
{
    protected $table = 'pedidos_web_items';
    
    protected $fillable = [
        'pedido_web_id',
        'producto_id',
        'precio_venta_id',
        'unidad_medida_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'descuento',
        'tasa_isv',
        'isv',
        'total',
    ];
    
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'tasa_isv' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoWeb::class, 'pedido_web_id');
    }
    
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
