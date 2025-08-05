<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamento';
    
    protected $fillable = [
        'nombre',
        'user_registro_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con el usuario que registró el departamento
    public function userRegistro()
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }

    // Relación con los municipios del departamento
    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'departamento_id');
    }
}
