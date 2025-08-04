<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecibidoBodega extends Model
{
    use HasFactory;

    protected $table = 'recibido_bodega';

    protected $fillable = [
        'producto_id',
        'seccion_id',
        'cantidad_compra_lote',
        'cantidad_inicial_seccion',
        'cantidad_disponible',
        'fecha_recibido',
        'fecha_expiracion',
        'comentario',
        'unidades_compra',
        'unidad_compra_id',
        'users_registro_id',
        'estado_id'
    ];

    protected $casts = [
        'fecha_recibido' => 'date',
        'fecha_expiracion' => 'date'
    ];

    // Relationships
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function unidadCompra()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_compra_id');
    }

    public function userRegistro()
    {
        return $this->belongsTo(User::class, 'users_registro_id');
    }
}
