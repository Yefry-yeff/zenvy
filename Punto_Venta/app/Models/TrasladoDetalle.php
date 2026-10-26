<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrasladoDetalle extends Model
{
    protected $table = 'traslado_detalle';

    protected $fillable = [
        'traslado_id',
        'producto_id',
        'producto_externo_id',
        'precio_venta_id',
        'unidad_medida_id',
        'cantidad_solicitada',
        'cantidad_enviada',
        'cantidad_recibida',
        'cantidad_rechazada',
        'observacion',
    ];

    public function traslado()
    {
        return $this->belongsTo(Traslado::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function precioVenta()
    {
        return $this->belongsTo(PrecioHasVenta::class, 'precio_venta_id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function lotes()
    {
        return $this->hasMany(TrasladoLote::class);
    }
}
