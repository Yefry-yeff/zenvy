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
