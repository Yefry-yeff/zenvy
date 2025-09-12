<?php

namespace App\Services;

use App\Models\MarcaExterna;
use App\Models\Marca;
use App\Models\IdZenvyValencia;
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
                
                // Validar que el resultado sea uno de los valores esperados
                if (isset($estadisticas[$resultado])) {
                    $estadisticas[$resultado]++;
                } else {
                    Log::warning("Resultado inesperado en procesarMarcaIndividual: '$resultado'");
                    $estadisticas['sin_cambios']++; // Fallback
                }
                
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
     * Procesa una marca individual usando tabla de mapeo para evitar duplicados
     */
    private function procesarMarcaIndividual($marcaExterna)
    {
        try {
            // Limpiar el nombre de la marca
            $nombreLimpio = trim($marcaExterna->nombre);
            
            if (empty($nombreLimpio)) {
                return 'sin_cambios';
            }

            // 1. Verificar si ya existe mapeo por ID de Valencia
            $mapeoExistente = IdZenvyValencia::buscarPorValencia($marcaExterna->id, IdZenvyValencia::TIPO_MARCA);
            
            if ($mapeoExistente) {
                // Ya existe mapeo, actualizar la marca existente
                $marcaLocal = Marca::find($mapeoExistente->id_zenvy);
                
                if ($marcaLocal) {
                    // Verificar si el nombre cambió
                    if ($marcaLocal->nombre !== $nombreLimpio) {
                        $marcaLocal->update([
                            'nombre' => $nombreLimpio,
                            'updated_at' => now()
                        ]);
                        
                        // Actualizar timestamp del mapeo
                        $mapeoExistente->touch();
                        
                        Log::info("Marca actualizada por mapeo: {$nombreLimpio} (ID Valencia: {$marcaExterna->id}, ID Zenvy: {$marcaLocal->id})");
                        return 'actualizadas';
                    } else {
                        // Sin cambios en el nombre
                        return 'sin_cambios';
                    }
                } else {
                    // El mapeo existe pero la marca local no existe (inconsistencia)
                    Log::warning("Inconsistencia: Mapeo existe pero marca no encontrada. ID Zenvy: {$mapeoExistente->id_zenvy}");
                    $mapeoExistente->delete(); // Limpiar mapeo inconsistente
                }
            }
            
            // 2. Verificar si existe marca local por nombre (para crear mapeo retroactivo)
            $marcaLocal = Marca::where('nombre', $nombreLimpio)->first();
            
            if ($marcaLocal) {
                // Marca existe pero sin mapeo, crear mapeo
                IdZenvyValencia::crearMapeo($marcaLocal->id, $marcaExterna->id, IdZenvyValencia::TIPO_MARCA);
                
                // Actualizar timestamp de la marca
                $marcaLocal->touch();
                
                Log::info("Mapeo creado para marca existente: {$nombreLimpio} (ID Valencia: {$marcaExterna->id}, ID Zenvy: {$marcaLocal->id})");
                return 'actualizadas';
            }
            
            // 3. Crear nueva marca con mapeo
            $nuevaMarca = Marca::create([
                'nombre' => $nombreLimpio,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Crear mapeo para la nueva marca
            IdZenvyValencia::crearMapeo($nuevaMarca->id, $marcaExterna->id, IdZenvyValencia::TIPO_MARCA);
            
            Log::info("Nueva marca creada con mapeo: {$nombreLimpio} (ID Valencia: {$marcaExterna->id}, ID Zenvy: {$nuevaMarca->id})");
            return 'nuevas';

        } catch (\Exception $e) {
            Log::error("Error procesando marca '{$marcaExterna->nombre}' (ID: {$marcaExterna->id}): " . $e->getMessage());
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

            // Obtener IDs de marcas externas
            $idsExternos = MarcaExterna::pluck('id')->filter()->toArray();
            
            if (empty($idsExternos)) {
                throw new \Exception('No se pudieron obtener IDs de marcas externas para comparar');
            }

            // Encontrar mapeos de marcas que no existen externamente
            $mapeosOrfanos = IdZenvyValencia::where('tipo_dato_migrado_id', IdZenvyValencia::TIPO_MARCA)
                                          ->whereNotIn('id_valencia', $idsExternos)
                                          ->get();
            
            $eliminadas = 0;
            foreach ($mapeosOrfanos as $mapeo) {
                $marca = Marca::find($mapeo->id_zenvy);
                
                if ($marca) {
                    // Verificar que la marca no esté siendo usada por productos
                    $productosUsando = DB::table('producto')->where('marca_id', $marca->id)->count();
                    
                    if ($productosUsando == 0) {
                        $marca->delete();
                        $mapeo->delete(); // Eliminar también el mapeo
                        $eliminadas++;
                        Log::info("Marca órfana eliminada con mapeo: {$marca->nombre} (ID Valencia: {$mapeo->id_valencia})");
                    } else {
                        Log::info("Marca órfana preservada (en uso): {$marca->nombre} (ID Valencia: {$mapeo->id_valencia})");
                    }
                } else {
                    // Mapeo sin marca (inconsistencia), eliminar mapeo
                    $mapeo->delete();
                    Log::info("Mapeo huérfano eliminado: ID Valencia {$mapeo->id_valencia}");
                }
            }

            return [
                'success' => true,
                'eliminadas' => $eliminadas,
                'encontradas' => $mapeosOrfanos->count(),
                'message' => "Se eliminaron {$eliminadas} marcas órfanas de {$mapeosOrfanos->count()} encontradas (usando mapeos)"
            ];

        } catch (\Exception $e) {
            Log::error('Error limpiando marcas órfanas: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crea mapeos retroactivos para marcas ya sincronizadas
     */
    public function crearMapeosRetroactivos()
    {
        try {
            $conectividad = $this->verificarConectividad();
            if (!$conectividad['status']) {
                throw new \Exception('Sin conectividad para crear mapeos retroactivos');
            }

            $marcasExternas = MarcaExterna::obtenerMarcasExternas();
            $mapeosCreados = 0;

            DB::beginTransaction();

            foreach ($marcasExternas as $marcaExterna) {
                $nombreLimpio = trim($marcaExterna->nombre);
                
                if (empty($nombreLimpio)) {
                    continue;
                }

                // Verificar si ya existe mapeo
                $mapeoExistente = IdZenvyValencia::buscarPorValencia($marcaExterna->id, IdZenvyValencia::TIPO_MARCA);
                
                if (!$mapeoExistente) {
                    // Buscar marca local por nombre
                    $marcaLocal = Marca::where('nombre', $nombreLimpio)->first();
                    
                    if ($marcaLocal) {
                        // Crear mapeo retroactivo
                        IdZenvyValencia::crearMapeo($marcaLocal->id, $marcaExterna->id, IdZenvyValencia::TIPO_MARCA);
                        $mapeosCreados++;
                        
                        Log::info("Mapeo retroactivo creado: {$nombreLimpio} (ID Valencia: {$marcaExterna->id}, ID Zenvy: {$marcaLocal->id})");
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'mapeos_creados' => $mapeosCreados,
                'message' => "Se crearon {$mapeosCreados} mapeos retroactivos"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando mapeos retroactivos: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
