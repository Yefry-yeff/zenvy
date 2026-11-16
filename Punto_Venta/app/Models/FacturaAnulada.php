<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaAnulada extends Model
{
    protected $table = 'facturas_anuladas';

    protected $fillable = [
        'factura_id',
        'numero_factura',
        'nombre_cliente',
        'rtn',
        'sub_total',
        'isv',
        'total',
        'fecha_emision_factura',
        'fecha_anulacion',
        'motivo_anulacion',
        'users_id_anulo',
        'users_id_vendedor',
        'productos_devueltos',
        'impacto_flujo_caja',
        'metodo_devolucion',
        'observaciones'
    ];

    protected $casts = [
        'productos_devueltos' => 'array',
        'fecha_emision_factura' => 'date',
        'fecha_anulacion' => 'datetime',
        'sub_total' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
        'impacto_flujo_caja' => 'decimal:2'
    ];

    // Relación con factura
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    // Relación con usuario que anuló
    public function usuarioAnulo()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id_anulo');
    }

    // Relación con vendedor original
    public function vendedor()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id_vendedor');
    }
}
