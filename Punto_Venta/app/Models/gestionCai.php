<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionCai extends Model
{
    protected $table = 'gestion_cai';
    protected $fillable = [

        'id',
        'numero_actual',
        'numero_base',
        'serie',
        'cantidad_no_utilizada',
        'tipo_documento_fiscal_id',
        'cantidad_solicitada',
        'cantidad_otorgada',
        'cai_id',
        'estado_id',
        'created_at',
        'updated_at'

    ];



}
