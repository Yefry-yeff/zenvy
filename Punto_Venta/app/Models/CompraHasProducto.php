<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraHasProducto extends Model
{
    use HasFactory;

    protected $table = 'compra_has_producto';

    public $timestamps = false; // Deshabilitar timestamps automáticos

    protected $fillable = [
        'precio',
        'cantidad_recibida',       // Nueva: cantidad de paquetes/cajas/etc recibidos
        'cantidad_ingresada',      // Cantidad unitaria que se ingresa al stock
        'cantidad_sin_asignar',
        'fecha_expiracion',
        'sub_total_producto',
        'isv',
        'precio_total',
        'compra_id',
        'producto_id',
        'unidad_medida_id'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'sub_total_producto' => 'decimal:2',
        'isv' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'fecha_expiracion' => 'date',
    ];

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Relación con la compra
     */
    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    /**
     * Relación con la unidad de medida
     */
    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }
}
