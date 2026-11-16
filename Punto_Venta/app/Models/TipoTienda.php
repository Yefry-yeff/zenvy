<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTienda extends Model
{
    use HasFactory;

    protected $table = 'tipo_tienda';

    protected $fillable = [
        'nombre',
        'users_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    // Relación con tiendas
    public function tiendas()
    {
        return $this->hasMany(Tienda::class, 'tipo_tienda_id');
    }
}
