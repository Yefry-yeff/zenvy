<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetalle extends Model
{
  protected $table = 'user_detalle';
    protected $fillable = [
        'users_id',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'direccion',
        'telefono',
        'genero',
        'fecha_nacimiento',
        'identidad',
        'estado_id',
        'created_user',
        'update_user',
];

}
