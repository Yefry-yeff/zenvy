<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tiendas extends Model
{
    protected $table = 'tienda';
    protected $fillable = [

        'id',
        'denominacion_social',
        'descripcion',
        'telefono',
        'celular',
        'correo',
        'tipo_tienda_id',
        'estado_id',
        'users_creador_id',
        'numero_sucursal',
        'identificador_legal',
        'direccion_sucursal_id',
        'created_at',
        'updated_at'

    ];



}
