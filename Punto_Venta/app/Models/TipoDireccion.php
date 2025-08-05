<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDireccion extends Model
{
    use HasFactory;

    protected $table = 'tipo_direccion';

    protected $fillable = [
        'nombre',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con direcciones
    public function direcciones()
    {
        return $this->hasMany(Direccion::class, 'tipo_direccion_id');
    }
}
