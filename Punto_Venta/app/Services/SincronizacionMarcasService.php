<?php

namespace App\Services;

use App\Models\MarcaExterna;
use App\Models\Marca;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SincronizacionMarcasService
{
    /**
     * Sincroniza marcas desde la base externa y las inserta/actualiza localmente
     */
    public function sincronizarMarcasEnTiempoReal()
    {
        // Usar cache para evitar consultas excesivas (cache por 5 minutos)
        return Cache::remember('marcas_sincronizadas', 300, function () {
            return $this->ejecutarSincronizacion();
        });
    }

    /**
     * Fuerza la sincronización (ignora cache)
     */
    public function forzarSincronizacion()
    {
        Cache::forget('marcas_sincronizadas');
        Cache::forget('marcas_externas_directas');
        return $this->ejecutarSincronizacion();
    }

    /**
     * Obtiene marcas directamente desde la base externa y las sincroniza localmente
     */
    public function obtenerMarcasDirectas()
    {
        try {
            // Sincronizar primero desde la base externa
            $this->sincronizarDesdeBaseExterna();

            // Retornar marcas locales actualizadas
            return Marca::select('id', 'nombre')->orderBy('nombre')->get();

        } catch (\Exception $e) {
            Log::error('Error al obtener marcas directas: ' . $e->getMessage());
            // Fallback a marcas locales existentes
            return Marca::select('id', 'nombre')->orderBy('nombre')->get();
        }
    }

    /**
     * Sincroniza marcas desde la base externa a la tabla local
     */
    public function sincronizarDesdeBaseExterna()
    {
        try {
            // Verificar conectividad primero
            $conectividad = $this->verificarConectividad();
            if (!$conectividad['status']) {
                Log::warning('Sin conectividad con base externa: ' . $conectividad['message']);
                return [
                    'success' => false,
                    'message' => 'Sin conectividad con sistema externo'
                ];
            }

            // Obtener marcas desde la base externa
            $marcasExternas = MarcaExterna::obtenerMarcasExternas();

            if ($marcasExternas->isEmpty()) {
                Log::warning('No se encontraron marcas en la base externa');
                return [
                    'success' => false,
                    'message' => 'No hay marcas en el sistema externo'
                ];
            }

            // Sincronizar con la tabla local
            $estadisticas = $this->procesarMarcasExternas($marcasExternas);

            Log::info('Sincronización completada', $estadisticas);

            return [
                'success' => true,
                'message' => "Sincronización exitosa: {$estadisticas['nuevas']} nuevas, {$estadisticas['actualizadas']} actualizadas",
                'estadisticas' => $estadisticas
            ];

        } catch (\Exception $e) {
            Log::error('Error en sincronización desde base externa: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesa las marcas externas y las inserta/actualiza en la tabla local
     */
    private function procesarMarcasExternas($marcasExternas)
    {
        $estadisticas = [
            'nuevas' => 0,
            'actualizadas' => 0,
            'sin_cambios' => 0,
            'total_procesadas' => 0
        ];

        DB::beginTransaction();
        
        try {
            foreach ($marcasExternas as $marcaExterna) {
                $resultado = $this->procesarMarcaIndividual($marcaExterna);
                $estadisticas[$resultado]++;
                $estadisticas['total_procesadas']++;
            }

            DB::commit();
            Log::info('Transacción de sincronización completada', $estadisticas);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en transacción de sincronización: ' . $e->getMessage());
            throw $e;
        }

        return $estadisticas;
    }

    /**
     * Procesa una marca individual
     */
    private function procesarMarcaIndividual($marcaExterna)
    {
        try {
            // Limpiar el nombre de la marca
            $nombreLimpio = trim($marcaExterna->nombre);
            
            if (empty($nombreLimpio)) {
                return 'sin_cambios';
            }

            // Buscar si ya existe la marca localmente
            $marcaLocal = Marca::where('nombre', $nombreLimpio)->first();

            if ($marcaLocal) {
                // La marca ya existe - verificar si necesita actualización
                if ($marcaLocal->updated_at < now()->subDays(1)) {
                    // Actualizar timestamp si tiene más de un día
                    $marcaLocal->touch();
                    return 'actualizadas';
                }
                return 'sin_cambios';
            } else {
                // Crear nueva marca en la tabla local
                Marca::create([
                    'nombre' => $nombreLimpio,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info("Nueva marca sincronizada: {$nombreLimpio}");
                return 'nuevas';
            }

        } catch (\Exception $e) {
            Log::error("Error procesando marca '{$marcaExterna->nombre}': " . $e->getMessage());
            return 'sin_cambios';
        }
    }

    /**
     * Ejecuta la sincronización real
     */
    private function ejecutarSincronizacion()
    {
        try {
            // Verificar conectividad primero
            $conectividad = $this->verificarConectividad();
            if (!$conectividad['status']) {
                throw new \Exception($conectividad['message']);
            }

            return $this->sincronizarDesdeBaseExterna();

        } catch (\Exception $e) {
            Log::error('Error en sincronización: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la sincronización: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica conectividad con la base externa
     */
    public function verificarConectividad()
    {
        try {
            DB::connection('profac_app')->getPdo();
            return ['status' => true, 'message' => 'Conexión exitosa'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene estadísticas de sincronización
     */
    public function obtenerEstadisticas()
    {
        try {
            $marcasLocales = Marca::count();
            $marcasExternas = Cache::remember('count_marcas_externas', 300, function () {
                try {
                    return MarcaExterna::count();
                } catch (\Exception $e) {
                    return 0;
                }
            });

            $conectividad = $this->verificarConectividad();
            $ultimaSincronizacion = Cache::get('marcas_sincronizadas');

            return [
                'marcas_locales' => $marcasLocales,
                'marcas_externas' => $marcasExternas,
                'conectividad' => $conectividad['status'],
                'ultimo_cache' => $ultimaSincronizacion ? 'Activo' : 'Inactivo',
                'diferencia' => $marcasExternas - $marcasLocales
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'marcas_locales' => Marca::count(),
                'conectividad' => false
            ];
        }
    }

    /**
     * Limpia marcas locales que no existen en la base externa
     */
    public function limpiarMarcasOrfanas()
    {
        try {
            $conectividad = $this->verificarConectividad();
            if (!$conectividad['status']) {
                throw new \Exception('Sin conectividad para verificar marcas órfanas');
            }

            // Obtener nombres de marcas externas
            $nombresExternos = MarcaExterna::pluck('nombre')->filter()->toArray();
            
            if (empty($nombresExternos)) {
                throw new \Exception('No se pudieron obtener marcas externas para comparar');
            }

            // Encontrar marcas locales que no existen externamente
            $marcasOrfanas = Marca::whereNotIn('nombre', $nombresExternos)->get();
            
            $eliminadas = 0;
            foreach ($marcasOrfanas as $marca) {
                // Verificar que la marca no esté siendo usada por productos
                $productosUsando = DB::table('producto')->where('marca_id', $marca->id)->count();
                
                if ($productosUsando == 0) {
                    $marca->delete();
                    $eliminadas++;
                    Log::info("Marca órfana eliminada: {$marca->nombre}");
                }
            }

            return [
                'success' => true,
                'eliminadas' => $eliminadas,
                'encontradas' => $marcasOrfanas->count(),
                'message' => "Se eliminaron {$eliminadas} marcas órfanas de {$marcasOrfanas->count()} encontradas"
            ];

        } catch (\Exception $e) {
            Log::error('Error limpiando marcas órfanas: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
