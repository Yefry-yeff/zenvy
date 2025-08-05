<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $table = 'estado';

    protected $fillable = [
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con tiendas
    public function tiendas()
    {
        return $this->hasMany(Tienda::class, 'estado_id');
    }

    // Relación con direcciones
    public function direcciones()
    {
        return $this->hasMany(Direccion::class, 'estado_id');
    }

    // Accessor para obtener 'nombre' desde 'descripcion' (para compatibilidad)
    public function getNombreAttribute()
    {
        return $this->descripcion;
    }
}
