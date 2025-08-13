<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    use HasFactory;

    protected $table = 'descuentos';

    protected $fillable = [
        'factura_id',
        'producto_id',
        'monto_unidad',
        'monto_total',
        'users_id'
    ];

    protected $casts = [
        'monto_unidad' => 'decimal:2',
        'monto_total' => 'decimal:2',
    ];

    // Relaciones
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
