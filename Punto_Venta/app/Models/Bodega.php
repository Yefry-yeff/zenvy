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
        'estado_id',
        'principal'
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

    public function direccion()
    {
        return $this->belongsTo(Direccion::class, 'direccion_id');
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
        // Verificar si la tienda ya tiene una bodega principal
        $tienePrincipal = self::where('tienda_id', $datos['tienda_id'])
                            ->where('principal', 1)
                            ->where('estado_id', 1)
                            ->exists();

        // Si no tiene bodega principal, esta será la principal (1), sino será secundaria (0)
        $datos['principal'] = $tienePrincipal ? 0 : 1;

        // Mapear los nombres de campos del formulario a los de la base de datos
        $datosMapeados = [
            'nombre' => $datos['nombre'],
            'tienda_id' => $datos['tienda_id'],
            'direccion_id' => $datos['direccion_id'],
            'estado_id' => 1, // Siempre crear como activa
            'principal' => $datos['principal']
        ];

        return self::create($datosMapeados);
    }

    public static function actualizarBodega($id, $datos)
    {
        return self::where('id', $id)->update($datos);
    }

    public static function inactivarBodega($id)
    {
        return self::where('id', $id)->update(['estado_id' => 2]);
    }

    public static function activarBodega($id)
    {
        return self::where('id', $id)->update(['estado_id' => 1]);
    }
}
