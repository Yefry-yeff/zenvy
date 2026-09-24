<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaExterna extends Model
{
    protected $connection = 'distrib3_valencia_produccion';
    protected $table = 'categoria_producto';
    
    protected $fillable = [
        'id',
        'descripcion'
    ];

    public $timestamps = false;
    
    protected $primaryKey = 'id';
}
