<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacora';

    // La tabla no usa timestamps automáticos de Laravel, solo created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'users_id',
        'modulo',
        'accion',
        'descripcion',
        'idReferencia',
        'tablaReferencia',
        'datosAnteriores',
        'datosNuevos',
        'ip_Equipo',
        'created_at'
    ];

    protected $casts = [
        'datosAnteriores' => 'array',
        'datosNuevos' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relación con el usuario que realizó la acción
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    /**
     * Método estático para registrar una acción en la bitácora
     */
    public static function registrar(
        $userId,
        $modulo,
        $accion,
        $descripcion,
        $idReferencia = null,
        $tablaReferencia = null,
        $datosAnteriores = null,
        $datosNuevos = null,
        $ipEquipo = null
    ) {
        try {
            return self::create([
                'users_id' => $userId,
                'modulo' => $modulo,
                'accion' => $accion,
                'descripcion' => $descripcion,
                'idReferencia' => $idReferencia,
                'tablaReferencia' => $tablaReferencia,
                'datosAnteriores' => $datosAnteriores,
                'datosNuevos' => $datosNuevos,
                'ip_Equipo' => $ipEquipo ?? request()->ip(),
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo principal
            \Log::error('Error al registrar en bitácora', [
                'error' => $e->getMessage(),
                'modulo' => $modulo,
                'accion' => $accion,
                'user_id' => $userId
            ]);
        }
    }
}