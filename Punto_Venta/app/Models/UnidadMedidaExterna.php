<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadMedidaExterna extends Model
{
    protected $connection = 'profac_app';
    protected $table = 'unidad_medida';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'id',
        'unidad',
        'nombre', 
        'simbolo',
        'created_at',
        'updated_at'
    ];
    
    public $timestamps = true;
    
    protected $dates = [
        'created_at',
        'updated_at'
    ];
    
    protected $casts = [
        'id' => 'integer',
        'unidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
