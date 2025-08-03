<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    use HasFactory;

    protected $table = 'direccion';

    protected $fillable = [
        'domicilio_tributario',
        'colonia',
        'calle_blv',
        'sector_zona',
        'bloque',
        'tipo_direccion_id',
        'municipio_id',
        'estado_id',
        'latitud',
        'longitud'
    ];

    // Crear nueva dirección
    public static function crearDireccion($datos)
    {
        return self::create($datos);
    }

    // Obtener todas las direcciones activas
    public static function obtenerTodasActivas()
    {
        return self::where('estado_id', 1)->orderBy('domicilio_tributario')->get();
    }

    // Obtener dirección por ID
    public static function obtenerPorId($id)
    {
        return self::find($id);
    }

    // Actualizar dirección
    public static function actualizarDireccion($id, $datos)
    {
        return self::where('id', $id)->update($datos);
    }

    // Eliminar dirección (soft delete)
    public static function eliminarDireccion($id)
    {
        return self::where('id', $id)->update(['estado_id' => 0]);
    }

    // Método para mostrar dirección completa
    public function getDireccionCompletaAttribute()
    {
        $partes = array_filter([
            $this->domicilio_tributario,
            $this->colonia,
            $this->calle_blv,
            $this->sector_zona,
            $this->bloque
        ]);
        
        return implode(', ', $partes);
    }
}
