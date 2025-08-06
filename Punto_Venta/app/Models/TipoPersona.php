<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPersona extends Model
{
    use HasFactory;

    protected $table = 'tipo_persona';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    // Relationships
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    // Scopes - since there's no estado_id, return all records
    public function scopeActivos($query)
    {
        return $query;
    }
}
