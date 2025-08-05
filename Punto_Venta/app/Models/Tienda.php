<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    use HasFactory;

    protected $table = 'tienda';

    protected $fillable = [
        'denominacion_social',
        'descripcion',
        'telefono',
        'celular',
        'correo',
        'tipo_tienda_id',
        'estado_id',
        'users_creador_id',
        'numero_sucursal',
        'identificador_legal',
        'direccion_sucursal_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con el usuario creador
    public function userCreador()
    {
        return $this->belongsTo(User::class, 'users_creador_id');
    }

    // Relación con tipo de tienda
    public function tipoTienda()
    {
        return $this->belongsTo(TipoTienda::class, 'tipo_tienda_id');
    }

    // Relación con estado
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    // Relación con dirección
    public function direccion()
    {
        return $this->belongsTo(Direccion::class, 'direccion_sucursal_id');
    }
}
