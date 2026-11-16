<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segmento extends Model
{
    use HasFactory;

    protected $table = 'segmento';

    protected $fillable = [
        'descripcion',
        'bodega_id'
    ];

    // Relationships
    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }

    public function secciones()
    {
        return $this->hasMany(Seccion::class);
    }

    // Static methods
    public static function obtenerPorBodega($bodegaId)
    {
        return self::where('bodega_id', $bodegaId)
                   ->orderBy('descripcion')
                   ->get();
    }

    public static function crearSegmento($datos)
    {
        return self::create($datos);
    }

    public static function actualizarSegmento($id, $datos)
    {
        return self::where('id', $id)->update($datos);
    }

    public static function eliminarSegmento($id)
    {
        return self::where('id', $id)->delete();
    }
}
