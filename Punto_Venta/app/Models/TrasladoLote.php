<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrasladoLote extends Model
{
    protected $table = 'traslado_lote';

    protected $fillable = [
        'traslado_detalle_id',
        'recibido_bodega_id',
        'cantidad',
    ];
}
