<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'cliente';

    protected $fillable = [
        'nombre',
        'correo',
        'direccion_id',
        'estado_id',
        'identidad',
        'rtn',
        'tipo_persona_id',
        'tipo_cliente_id',
        'users_id'
    ];

    // Relationships
    public function direccion()
    {
        return $this->belongsTo(Direccion::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function tipoPersona()
    {
        return $this->belongsTo(TipoPersona::class);
    }

    public function tipoCliente()
    {
        return $this->belongsTo(TipoCliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function compras()
    {
        return $this->hasMany(Compra::class, 'cliente_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado_id', 1);
    }

    // Static methods for CRUD operations
    public static function crearCliente($data)
    {
        return self::create($data);
    }

    public static function actualizarCliente($id, $data)
    {
        $cliente = self::findOrFail($id);
        $cliente->update($data);
        return $cliente;
    }
}
