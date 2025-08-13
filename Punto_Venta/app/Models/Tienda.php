<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    use HasFactory;

    protected $table = 'tienda';

    protected $fillable = [
        'denominacion_social',
        'nombre_comercial',
        'rtn',
        'telefono',
        'correo',
        'estado_id',
        'direccion_sucursal_id',
    ];

    /**
     * Relación con usuarios
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'tienda_id');
    }

    /**
     * Scope para tiendas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado_id', 1);
    }
}
