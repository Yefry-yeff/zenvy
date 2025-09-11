<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubcategoriaExterna extends Model
{
    protected $connection = 'profac_app';
    protected $table = 'sub_categoria';
    
    protected $fillable = [
        'id',
        'descripcion',
        'categoria_producto_id'
    ];

    public $timestamps = false;
    
    protected $primaryKey = 'id';

    // Relación con categoría externa
    public function categoria()
    {
        return $this->belongsTo(CategoriaExterna::class, 'categoria_producto_id');
    }
}