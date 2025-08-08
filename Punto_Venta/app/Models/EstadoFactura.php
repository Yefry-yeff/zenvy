<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoFactura extends Model
{
    use HasFactory;

    protected $table = 'estado_factura';

    protected $fillable = [
        'nombre'
    ];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
}
