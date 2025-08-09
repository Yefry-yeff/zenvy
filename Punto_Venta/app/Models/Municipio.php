<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'municipio';

    protected $fillable = [
        'nombre',
        'departamento_id',
        'users_registro_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con el departamento al que pertenece
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    // Relación con el usuario que registró el municipio
    public function userRegistro()
    {
        return $this->belongsTo(User::class, 'users_registro_id');
    }
}
