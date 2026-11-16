<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cai extends Model
{
    protected $table = 'cai';
    protected $fillable = [

        'id',
        'cai',
        'fecha_limite_emision',
        'fecha_solicitud',
        'punto_emision',
        'tipo_documento_fiscal_id',
        'cantidad_solicitada',
        'cantidad_otorgada',
        'rango_inicio',
        'rango_final',
        'tienda_id',
        'users_registro_id',
        'estado_id',
        'created_at',
        'updated_at'

    ];



}
