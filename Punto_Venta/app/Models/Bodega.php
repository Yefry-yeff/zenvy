<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Bodega extends Model
{
    use HasFactory;

    protected $table = 'bodega';

    protected $fillable = [
        'nombre',
        'tienda_id',
        'direccion_id',
        'estado_id'
    ];

    // Relationships
    public function segmentos()
    {
        return $this->hasMany(Segmento::class);
    }

    public function tienda()
    {
        return $this->belongsTo(Tiendas::class, 'tienda_id');
    }

    // Static methods for SP operations (cuando necesites stored procedures)
    public static function obtenerTodas()
    {
        return self::with(['segmentos.secciones'])
                   ->where('estado_id', 1)
                   ->orderBy(column: 'denominacion_social')
                   ->get();
    }

    public static function crearBodega($datos)
    {
        return self::create($datos);
    }

    public static function actualizarBodega($id, $datos)
    {
        return self::where('id', $id)->update($datos);
    }

    public static function eliminarBodega($id)
    {
        return self::where('id', $id)->update(['estado_id' => 0]);
    }
}
