<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadCompra extends Model
{
    use HasFactory;

    protected $table = 'unidad_compra';

    protected $fillable = [
        'unidad',
        'nombre',
        'simbolo',
    ];

    // Relación con los detalles de compra
    public function compraHasProductos()
    {
        return $this->hasMany(CompraHasProducto::class, 'unidad_compra_id');
    }
}
