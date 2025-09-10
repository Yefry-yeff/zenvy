<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'descuento_unitario',
        'descuento_tercera',
        'descuento_cuarta',
        'precio_base',
        'isv_id',
        'estado_id',
        'users_id',
        'imagen'
    ];

    // Relationships
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function isv()
    {
        return $this->belongsTo(Isv::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado_id', 1);
    }

    // Accessors
    public function getImagenBase64Attribute()
    {
        if ($this->imagen) {
            return 'data:image/*;base64,' . base64_encode($this->imagen);
        }
        return null;
    }

    // Static methods for CRUD operations
    public static function crearServicio($datos)
    {
        try {
            return self::create($datos);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en crearServicio: ' . $e->getMessage(), ['datos' => $datos]);
            throw $e;
        }
    }

    public static function actualizarServicio($id, $datos)
    {
        try {
            $servicio = self::findOrFail($id);
            return $servicio->update($datos);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en actualizarServicio: ' . $e->getMessage(), ['id' => $id, 'datos' => $datos]);
            throw $e;
        }
    }

    public static function eliminarServicio($id)
    {
        try {
            $servicio = self::findOrFail($id);
            $servicio->estado_id = 2; // Inactivo
            return $servicio->save();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en eliminarServicio: ' . $e->getMessage(), ['id' => $id]);
            throw $e;
        }
    }
}
