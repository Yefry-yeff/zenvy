<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $table = 'marca';
    
    protected $fillable = [
        'txt_descripcion',
        'estado_id'
    ];

    // Relationships
    public function productos()
    {
        return $this->hasMany(Producto::class, 'marca_id');
    }
}
