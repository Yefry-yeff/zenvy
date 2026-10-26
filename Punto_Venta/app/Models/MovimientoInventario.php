<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimiento_inventario';

    protected $fillable = [
        'traslado_id',
        'traslado_detalle_id',
        'recibido_bodega_id',
        'producto_id',
        'bodega_id',
        'tipo',
        'cantidad',
        'saldo_anterior',
        'saldo_nuevo',
        'referencia',
        'usuario_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
