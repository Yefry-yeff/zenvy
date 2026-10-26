<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Traslado extends Model
{
    protected $table = 'traslado';

    protected $fillable = [
        'uuid',
        'codigo',
        'direccion',
        'sistema_origen',
        'referencia_externa',
        'bodega_origen_id',
        'bodega_destino_id',
        'estado',
        'fecha_solicitud',
        'fecha_despacho',
        'fecha_recepcion',
        'usuario_creacion_id',
        'usuario_despacho_id',
        'usuario_recepcion_id',
        'comentario',
        'metadata',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_despacho' => 'datetime',
        'fecha_recepcion' => 'datetime',
        'metadata' => 'array',
    ];

    public function detalles()
    {
        return $this->hasMany(TrasladoDetalle::class);
    }

    public function bodegaOrigen()
    {
        return $this->belongsTo(Bodega::class, 'bodega_origen_id');
    }

    public function bodegaDestino()
    {
        return $this->belongsTo(Bodega::class, 'bodega_destino_id');
    }
}
