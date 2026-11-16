<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $table = 'marca';
    public $timestamps = true; // Habilitar timestamps

    protected $fillable = [
        'nombre',
        'created_at',
        'updated_at'
    ];

    // Relationships
    public function productos()
    {
        return $this->hasMany(Producto::class, 'marca_id');
    }

    /**
     * Scope para obtener marcas sincronizadas recientemente
     */
    public function scopeRecientementeSincronizadas($query, $horas = 24)
    {
        return $query->where('updated_at', '>=', now()->subHours($horas));
    }

    /**
     * Verifica si la marca está siendo usada por productos
     */
    public function estaEnUso()
    {
        return $this->productos()->exists();
    }
}
