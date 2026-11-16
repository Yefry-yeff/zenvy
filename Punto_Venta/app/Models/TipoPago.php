<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPago extends Model
{
    protected $table = 'tipo_pago';

    protected $fillable = [
        'nombre'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
