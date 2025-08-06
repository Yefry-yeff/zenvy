<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraHasProducto extends Model
{
    use HasFactory;

    protected $table = 'compra_has_producto';

    protected $fillable = [
        'precio',
        'cantidad_ingresada',
        'cantidad_sin_asignar',
        'fecha_expiracion',
        'sub_total_producto',
        'isv',
        'precio_total',
        'compra_id',
        'producto_id',
        'unidad_compra_id',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'sub_total_producto' => 'decimal:2',
        'isv' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_expiracion' => 'date',
    ];

    // Relación con la compra
    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    // Relación con el producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Relación con la unidad de compra
    public function unidadCompra()
    {
        return $this->belongsTo(UnidadCompra::class, 'unidad_compra_id');
    }
}
