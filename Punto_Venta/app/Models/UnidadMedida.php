<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'unidad_medida';

    protected $fillable = [
        'unidad',
        'nombre',
        'simbolo'
    ];

    // Relationships
    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidad_medida_venta_id');
    }
}
