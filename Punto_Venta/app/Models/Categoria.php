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
        return $this->hasMany(Subcategoria::class, 'categoria_id');
    }

    // Relación para obtener todos los productos de todas las subcategorías de esta categoría
    public function productos()
    {
        return $this->hasManyThrough(Producto::class, Subcategoria::class, 'categoria_id', 'subcategoria_id');
    }
}
