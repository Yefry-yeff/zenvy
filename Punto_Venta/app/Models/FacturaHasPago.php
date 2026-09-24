<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaHasPago extends Model
{
    protected $table = 'factura_has_pago';

    protected $fillable = [
        'factura_id',
        'tipo_pago_id',
        'total_factura',
        'pago_recibido',
        'cambio'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function tipoPago()
    {
        return $this->belongsTo(TipoPago::class);
    }
}
