<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoValenciaZenvy extends Model
{
    protected $table = 'producto_valencia_zenvy';
    
    protected $fillable = [
        'producto_id_zenvy',
        'producto_id_valencia',
        'codigo_producto_valencia',
        'codigo_barra',
        'sincronizado',
        'ultima_sincronizacion',
    ];

    protected $casts = [
        'sincronizado' => 'boolean',
        'ultima_sincronizacion' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Relación con el producto de Zenvy
     */
    public function producto()
    {
        return $this->belongsTo(\App\Models\Producto::class, 'producto_id_zenvy', 'id');
    }

    /**
     * Busca por ID de producto Valencia
     */
    public static function buscarPorIdValencia($idValencia)
    {
        return self::where('producto_id_valencia', $idValencia)->first();
    }

    /**
     * Busca por ID de producto Zenvy
     */
    public static function buscarPorIdZenvy($idZenvy)
    {
        return self::where('producto_id_zenvy', $idZenvy)->first();
    }

    /**
     * Busca por código de producto Valencia
     */
    public static function buscarPorCodigoValencia($codigo)
    {
        return self::where('codigo_producto_valencia', $codigo)->first();
    }

    /**
     * Busca por código de barras
     */
    public static function buscarPorCodigoBarra($codigoBarra)
    {
        return self::where('codigo_barra', $codigoBarra)->first();
    }

    /**
     * Crea o actualiza el mapeo de producto
     */
    public static function crearOActualizar($idZenvy, $idValencia, $codigoValencia = null, $codigoBarra = null)
    {
        return self::updateOrCreate(
            ['producto_id_valencia' => $idValencia],
            [
                'producto_id_zenvy' => $idZenvy,
                'codigo_producto_valencia' => $codigoValencia,
                'codigo_barra' => $codigoBarra,
                'sincronizado' => true,
                'ultima_sincronizacion' => now(),
            ]
        );
    }

    /**
     * Obtiene todos los productos sincronizados
     */
    public static function productosSincronizados()
    {
        return self::where('sincronizado', true)->get();
    }

    /**
     * Obtiene estadísticas de productos sincronizados
     */
    public static function estadisticas()
    {
        return [
            'total_sincronizados' => self::where('sincronizado', true)->count(),
            'total_registros' => self::count(),
            'ultima_sincronizacion' => self::max('ultima_sincronizacion'),
        ];
    }

    /**
     * Marca un producto como no sincronizado
     */
    public function marcarComoNoSincronizado()
    {
        $this->sincronizado = false;
        $this->save();
    }

    /**
     * Actualiza la fecha de última sincronización
     */
    public function actualizarSincronizacion()
    {
        $this->ultima_sincronizacion = now();
        $this->sincronizado = true;
        $this->save();
    }

    /**
     * Obtiene el ID de Zenvy desde Valencia (método estático rápido)
     */
    public static function obtenerIdZenvy($idValencia)
    {
        $mapeo = self::where('producto_id_valencia', $idValencia)
            ->where('sincronizado', true)
            ->first();
        
        return $mapeo ? $mapeo->producto_id_zenvy : null;
    }

    /**
     * Obtiene el ID de Valencia desde Zenvy (método estático rápido)
     */
    public static function obtenerIdValencia($idZenvy)
    {
        $mapeo = self::where('producto_id_zenvy', $idZenvy)
            ->where('sincronizado', true)
            ->first();
        
        return $mapeo ? $mapeo->producto_id_valencia : null;
    }

    /**
     * Verifica si un producto está sincronizado
     */
    public static function estaSincronizado($idValencia)
    {
        return self::where('producto_id_valencia', $idValencia)
            ->where('sincronizado', true)
            ->exists();
    }
}
