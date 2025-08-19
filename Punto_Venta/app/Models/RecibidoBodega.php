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
        'unidad_medida_id',
        'users_registro_id',
        'estado_id'
    ];

    protected $casts = [
        'fecha_recibido' => 'date',
        'fecha_expiracion' => 'date',
    ];

    // Relaciones
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
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_registro_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
}
