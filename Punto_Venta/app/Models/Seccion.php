<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use HasFactory;

    protected $table = 'seccion';

    protected $fillable = [
        'descripcion',
        'numeracion',
        'estado_id',
        'segmento_id'
    ];

    // Relationships
    public function segmento()
    {
        return $this->belongsTo(Segmento::class);
    }

    public function recibidosBodega()
    {
        return $this->hasMany(RecibidoBodega::class, 'seccion_id');
    }

    public function productos()
    {
        return $this->hasManyThrough(
            'App\Models\Producto',
            'App\Models\RecibidoBodega',
            'seccion_id', // Foreign key en recibido_bodega
            'id', // Foreign key en producto
            'id', // Local key en seccion
            'producto_id' // Local key en recibido_bodega
        );
    }

    // Static methods
    public static function obtenerPorSegmento($segmentoId)
    {
        return self::where('segmento_id', $segmentoId)
                   ->where('estado_id', 1)
                   ->orderBy('numeracion')
                   ->get();
    }

    public static function crearSeccion($datos)
    {
        return self::create($datos);
    }

    public static function actualizarSeccion($id, $datos)
    {
        return self::where('id', $id)->update($datos);
    }

    public static function eliminarSeccion($id)
    {
        return self::where('id', $id)->update(['estado_id' => 0]);
    }
}
