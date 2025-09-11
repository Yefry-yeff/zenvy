<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaExterna extends Model
{
    protected $connection = 'profac_app';
    protected $table = 'categoria_producto';
    
    protected $fillable = [
        'id',
        'descripcion'
    ];

    public $timestamps = false;
    
    protected $primaryKey = 'id';
}
