<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaHasProducto extends Model
{
    protected $table = 'factura_has_producto';
    
    public $timestamps = false;

    protected $fillable = [
        'factura_id',
        'producto_id',
        'seccion_id',
        'unidad_medida_id',
        'precio_id',
        'indice',
        'numero_unidades_resta_inventario',
        'unidades_nota_credito_resta_inventario',
        'resta_inventario_total',
        'precio_unidad',
        'cantidad',
        'subtotal',
        'descuento',
        'isv_aplicado',
        'isv',
        'total',
        'idPrecioSeleccionado',
        'precio_seleccionado',
        'Servicios_id'
    ];

    protected $casts = [
        'precio_unidad' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'isv_aplicado' => 'decimal:2',
        'isv' => 'decimal:2',
        'total' => 'decimal:2',
        'precio_seleccionado' => 'decimal:2'
    ];

    // Relaciones
    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }
}
