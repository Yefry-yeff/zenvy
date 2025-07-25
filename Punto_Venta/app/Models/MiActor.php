<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MiActor extends Model
{

    protected $fillable = [
    'txt_identificacion',
    'TXT_PRIMER_NOMBRE',
    'TXT_SEGUNDO_NOMBRE',
    'TXT_PRIMER_APELLIDO',
    'TXT_SEGUNDO_APELLIDO',
    'TXT_DIRECCION',
    'telefono',
    'genero',
    'fecha_nacimiento',
];
    protected $table = 'mi_actor'; // si el nombre no sigue la convención
}
