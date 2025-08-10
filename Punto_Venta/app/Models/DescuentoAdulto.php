<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescuentoAdulto extends Model
{
    use HasFactory;

    protected $table = 'descuento_adulto';

    protected $fillable = [
        'factura_id',
        'dni',
        'nombre',
        'edad'
    ];

    // Relación con factura
    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}
