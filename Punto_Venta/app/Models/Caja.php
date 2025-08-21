<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'caja';

    protected $fillable = [
        'tienda_id',
        'users_id',
        'balance',
        'estado_caja',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Relación con el usuario (cajero)
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    /**
     * Relación con la tienda
     */
    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    /**
     * Estados de caja
     */
    const ESTADO_CERRADA = 0;
    const ESTADO_ABIERTA = 1;
    const ESTADO_BLOQUEADA = 2;

    /**
     * Obtener el nombre del estado
     */
    public function getEstadoNombreAttribute()
    {
        switch($this->estado_caja) {
            case self::ESTADO_CERRADA:
                return 'Cerrada';
            case self::ESTADO_ABIERTA:
                return 'Abierta';
            case self::ESTADO_BLOQUEADA:
                return 'Bloqueada';
            default:
                return 'Desconocido';
        }
    }
}
