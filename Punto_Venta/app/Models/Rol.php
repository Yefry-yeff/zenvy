<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'txt_nombre',
        'descripcion',
        'estado_id',
    ];

    /**
     * Relación con usuarios
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    /**
     * Relación con permisos (muchos a muchos)
     */
    public function permisos()
    {
        return $this->belongsToMany('App\Models\Permiso', 'rol_has_permiso', 'rol_id', 'permiso_id');
    }

    /**
     * Scope para roles activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado_id', 1);
    }
}
