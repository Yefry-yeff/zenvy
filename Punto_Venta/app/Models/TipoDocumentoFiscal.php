<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumentoFiscal extends Model
{
    protected $table = 'tipo_documento_fiscal';
    protected $fillable = [

        'id',
        'nombre',
        'users_registro_id',
        'estado_id',
        'created_at',
        'updated_at'

    ];



}
