<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MarcaExterna extends Model
{
    protected $connection = 'profac_app'; // Usa la conexión externa
    protected $table = 'marca';
    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    /**
     * Obtiene todas las marcas de la base de datos externa
     */
    public static function obtenerMarcasExternas()
    {
        try {
            return self::select('id', 'nombre')
                      ->whereNotNull('nombre')
                      ->where('nombre', '!=', '')
                      ->orderBy('nombre')
                      ->get();
        } catch (\Exception $e) {
            Log::error('Error al obtener marcas externas: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Sincroniza las marcas externas con la base de datos local
     */
    public static function sincronizarMarcas()
    {
        try {
            $marcasExternas = self::obtenerMarcasExternas();
            $marcasSincronizadas = 0;

            foreach ($marcasExternas as $marcaExterna) {
                // Buscar o crear marca en la base de datos local
                $marcaLocal = \App\Models\Marca::firstOrCreate(
                    ['nombre' => $marcaExterna->nombre],
                    [
                        'nombre' => $marcaExterna->nombre,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                if ($marcaLocal->wasRecentlyCreated) {
                    $marcasSincronizadas++;
                    Log::info("Nueva marca sincronizada: {$marcaExterna->nombre}");
                }
            }

            return [
                'success' => true,
                'total_externas' => $marcasExternas->count(),
                'sincronizadas' => $marcasSincronizadas,
                'message' => "Sincronización completada: {$marcasSincronizadas} marcas nuevas agregadas de {$marcasExternas->count()} externas"
            ];

        } catch (\Exception $e) {
            Log::error('Error en sincronización de marcas: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la sincronización: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si una marca existe en la base externa
     */
    public static function existeEnSistemaExterno($nombre)
    {
        try {
            return self::where('nombre', $nombre)->exists();
        } catch (\Exception $e) {
            Log::error('Error verificando marca en sistema externo: ' . $e->getMessage());
            return false;
        }
    }
}
