<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compra';

    protected $fillable = [
        'numero_factura',
        'fecha_vencimiento',
        'fecha_emision',
        'fecha_recepcion',
        'estado_id',
        'cliente_id',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_emision' => 'date',
        'fecha_recepcion' => 'date',
    ];

    // Relación con los productos de la compra
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'compra_has_producto')
                    ->withPivot([
                        'id',
                        'precio',
                        'cantidad_ingresada',
                        'cantidad_sin_asignar',
                        'fecha_expiracion',
                        'sub_total_producto',
                        'isv',
                        'precio_total',
                        'unidad_compra_id'
                    ])
                    ->withTimestamps();
    }

    // Relación con los detalles de compra
    public function detallesCompra()
    {
        return $this->hasMany(CompraHasProducto::class, 'compra_id');
    }

    // Relación con el proveedor (cliente)
    public function proveedor()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Relación con el estado
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}
