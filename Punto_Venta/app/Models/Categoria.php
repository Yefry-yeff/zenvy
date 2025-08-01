<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categoria';

    protected $fillable = [
        'nombre',
    ];

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class, 'categoría_id');
    }
}
