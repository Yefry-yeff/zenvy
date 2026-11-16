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
        'bodega_id',
        'segmento_id',
        'cantidad_compra_lote',
        'cantidad_inicial_seccion',
        'cantidad_disponible',
        'cantidad_recibida',
        'fecha_recibido',
        'fecha_expiracion',
        'comentario',
        'observaciones',
        'unidades_compra',
        'unidad_medida',
        'unidad_medida_id',
        'precio_venta_id',
        'users_registro_id',
        'usuario_registro',
        'estado_id',
        'estado'
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

    // Relación directa con bodega (si existe bodega_id)
    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    // Relación directa con segmento (si existe segmento_id)
    public function segmento()
    {
        return $this->belongsTo(Segmento::class, 'segmento_id');
    }

    // Relaciones a través de seccion (para casos donde no hay campos directos)
    public function bodegaThroughSeccion()
    {
        return $this->hasOneThrough(
            Bodega::class,
            Seccion::class,
            'id', // Foreign key en seccion
            'id', // Foreign key en bodega
            'seccion_id', // Local key en recibido_bodega
            'segmento_id' // Local key en seccion que se relaciona con segmento
        )->through(Segmento::class);
    }

    public function segmentoThroughSeccion()
    {
        return $this->hasOneThrough(
            Segmento::class,
            Seccion::class,
            'id', // Foreign key en seccion
            'id', // Foreign key en segmento
            'seccion_id', // Local key en recibido_bodega
            'segmento_id' // Local key en seccion
        );
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

    public function distribucionesStock()
    {
        return $this->hasMany(DistribucionStock::class, 'recibido_bodega_id');
    }
}
