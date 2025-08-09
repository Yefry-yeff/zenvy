<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';
    
    protected $fillable = [
        'nombre',
        'rtn',
        'correo',
        'telefono',
        'logo'
    ];
    
    protected $casts = [
        'telefono' => 'integer'
    ];
    
    // Desactivar timestamps si no existen en la tabla
    public $timestamps = false;
}
