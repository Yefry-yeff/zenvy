<?php

namespace App\Services;

use App\Models\Subcategoria;
use App\Models\SubcategoriaExterna;
use App\Models\IdZenvyValencia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SincronizacionSubcategoriasService
{
    private const CACHE_KEY = 'subcategorias_sincronizadas';
    private const CACHE_DURATION = 300; // 5 minutos
    private const TIPO_DATO_SUBCATEGORIAS = 4; // Tipo para subcategorías en Valencia
    private const TIPO_DATO_CATEGORIAS = 3; // Tipo para categorías en Valencia

    public function sincronizarSubcategoriasEnTiempoReal()
    {
        // Si las subcategorías están en caché y son recientes, no sincronizar
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        return $this->forzarSincronizacion();
    }

    public function forzarSincronizacion()
    {
        try {
            $resultado = $this->sincronizarDesdeBaseExterna();
            
            // Guardar resultado en caché
            Cache::put(self::CACHE_KEY, $resultado, self::CACHE_DURATION);
            
            return $resultado;
        } catch (Exception $e) {
            Log::error('Error en sincronización forzada de subcategorías: ' . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerSubcategoriasDirectas()
    {
        // Intentar sincronización en tiempo real primero
        $this->sincronizarSubcategoriasEnTiempoReal();
        
        // Devolver todas las subcategorías locales con sus categorías
        return Subcategoria::with('categoria')->orderBy('nombre')->get(['id', 'nombre', 'categoria_id']);
    }

    private function sincronizarDesdeBaseExterna()
    {
        $stats = [
            'nuevas' => 0,
            'actualizadas' => 0,
            'sin_cambios' => 0,
            'errores' => 0
        ];

        try {
            // Verificar conectividad
            if (!$this->verificarConectividad()) {
                throw new Exception('No se puede conectar con la base de datos externa');
            }

            // Obtener subcategorías desde profac_app con sus categorías
            $subcategoriasExternas = SubcategoriaExterna::with('categoria')->get();
            
            Log::info("Sincronizando subcategorías. Total externas: " . $subcategoriasExternas->count());

            foreach ($subcategoriasExternas as $subcategoriaExterna) {
                $resultado = $this->procesarSubcategoriaIndividual($subcategoriaExterna);
                
                switch ($resultado) {
                    case 'nueva':
                        $stats['nuevas']++;
                        break;
                    case 'actualizada':
                        $stats['actualizadas']++;
                        break;
                    case 'sin_cambios':
                        $stats['sin_cambios']++;
                        break;
                    default:
                        $stats['errores']++;
                }
            }

            Log::info('Sincronización de subcategorías completada', $stats);
            return $stats;

        } catch (Exception $e) {
            Log::error('Error en sincronización de subcategorías: ' . $e->getMessage());
            throw $e;
        }
    }

    private function procesarSubcategoriaIndividual($subcategoriaExterna)
    {
        try {
            // Buscar si ya existe un mapeo para esta subcategoría de Valencia
            $mapeoExistente = IdZenvyValencia::buscarPorValencia($subcategoriaExterna->id, self::TIPO_DATO_SUBCATEGORIAS);
            
            // Buscar el mapeo de la categoría padre
            $mapeoCategoriaExterna = IdZenvyValencia::buscarPorValencia(
                $subcategoriaExterna->categoria_producto_id, 
                self::TIPO_DATO_CATEGORIAS
            );
            
            if (!$mapeoCategoriaExterna) {
                Log::warning("No se encontró mapeo para categoría externa ID: {$subcategoriaExterna->categoria_producto_id}");
                return 'error';
            }
            
            $categoriaLocalId = $mapeoCategoriaExterna->id_zenvy;
            
            if ($mapeoExistente) {
                // Actualizar subcategoría existente
                $subcategoriaLocal = Subcategoria::find($mapeoExistente->id_zenvy, ['id', 'nombre', 'categoria_id']);
                if ($subcategoriaLocal) {
                    $cambios = false;
                    
                    if ($subcategoriaLocal->nombre !== $subcategoriaExterna->descripcion) {
                        $subcategoriaLocal->nombre = $subcategoriaExterna->descripcion;
                        $cambios = true;
                    }
                    
                    if ($subcategoriaLocal->categoria_id !== $categoriaLocalId) {
                        $subcategoriaLocal->categoria_id = $categoriaLocalId;
                        $cambios = true;
                    }
                    
                    if ($cambios) {
                        $subcategoriaLocal->save();
                        return 'actualizada';
                    }
                    return 'sin_cambios';
                }
            } else {
                // Crear nueva subcategoría
                $nuevaSubcategoria = Subcategoria::create([
                    'nombre' => $subcategoriaExterna->descripcion,
                    'categoria_id' => $categoriaLocalId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Crear mapeo
                IdZenvyValencia::crearMapeo($nuevaSubcategoria->id, $subcategoriaExterna->id, self::TIPO_DATO_SUBCATEGORIAS);
                
                return 'nueva';
            }
        } catch (\Exception $e) {
            Log::error('Error procesando subcategoría individual: ' . $e->getMessage(), [
                'subcategoria_externa' => $subcategoriaExterna,
                'error' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return 'error';
    }

    public function verificarConectividad()
    {
        try {
            SubcategoriaExterna::first();
            return true;
        } catch (Exception $e) {
            Log::error('Error de conectividad con base externa: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $subcategoriasLocales = Subcategoria::count();
            $subcategoriasExternas = $this->verificarConectividad() ? SubcategoriaExterna::count() : 0;
            $conectividad = $this->verificarConectividad();
            $estadoCache = Cache::has(self::CACHE_KEY) ? 'Activo' : 'Inactivo';

            return [
                'Subcategorías Locales' => $subcategoriasLocales,
                'Subcategorías Externas' => $subcategoriasExternas,
                'Conectividad' => $conectividad ? '✅ Activa' : '❌ Inactiva',
                'Estado Cache' => $estadoCache
            ];
        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas de subcategorías: ' . $e->getMessage());
            return [
                'Error' => $e->getMessage()
            ];
        }
    }

    public function limpiarSubcategoriasOrfanas()
    {
        try {
            // Buscar mapeos que apuntan a subcategorías que ya no existen en Zenvy
            $mapeosOrfanos = IdZenvyValencia::where('tipo_dato_migrado_id', self::TIPO_DATO_SUBCATEGORIAS)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('subcategoria')
                          ->whereRaw('subcategoria.id = id_zenvy_valencia.id_zenvy');
                })
                ->get();

            $eliminados = 0;
            foreach ($mapeosOrfanos as $mapeo) {
                $mapeo->delete();
                $eliminados++;
            }

            Log::info("Limpieza de subcategorías órfanas completada. Eliminados: $eliminados");
            return $eliminados;

        } catch (Exception $e) {
            Log::error('Error limpiando subcategorías órfanas: ' . $e->getMessage());
            throw $e;
        }
    }

    public function crearMapeosRetroactivos()
    {
        try {
            if (!$this->verificarConectividad()) {
                throw new Exception('No se puede conectar con la base de datos externa');
            }

            $subcategoriasLocales = Subcategoria::with('categoria')->get();
            $subcategoriasExternas = SubcategoriaExterna::with('categoria')->get();
            
            $mapeosCreados = 0;
            
            foreach ($subcategoriasLocales as $subcategoriaLocal) {
                // Verificar si ya tiene mapeo
                $mapeoExistente = IdZenvyValencia::where('id_zenvy', $subcategoriaLocal->id)
                                                ->where('tipo_dato_migrado_id', self::TIPO_DATO_SUBCATEGORIAS)
                                                ->first();
                
                if (!$mapeoExistente) {
                    // Buscar subcategoría externa con nombre similar y categoría correspondiente
                    foreach ($subcategoriasExternas as $subcategoriaExterna) {
                        if ($subcategoriaExterna->descripcion === $subcategoriaLocal->nombre) {
                            // Verificar que la categoría padre también esté mapeada
                            $mapeoCategoriaExterna = IdZenvyValencia::buscarPorValencia(
                                $subcategoriaExterna->categoria_producto_id,
                                self::TIPO_DATO_CATEGORIAS
                            );
                            
                            if ($mapeoCategoriaExterna && $mapeoCategoriaExterna->id_zenvy === $subcategoriaLocal->categoria_id) {
                                // Verificar que el ID externo no esté ya mapeado
                                $mapeoExternoExistente = IdZenvyValencia::buscarPorValencia(
                                    $subcategoriaExterna->id, 
                                    self::TIPO_DATO_SUBCATEGORIAS
                                );
                                
                                if (!$mapeoExternoExistente) {
                                    IdZenvyValencia::crearMapeo(
                                        $subcategoriaLocal->id, 
                                        $subcategoriaExterna->id, 
                                        self::TIPO_DATO_SUBCATEGORIAS
                                    );
                                    $mapeosCreados++;
                                    break; // Salir del loop interno
                                }
                            }
                        }
                    }
                }
            }
            
            Log::info("Mapeos retroactivos de subcategorías creados: $mapeosCreados");
            return $mapeosCreados;
            
        } catch (Exception $e) {
            Log::error('Error creando mapeos retroactivos de subcategorías: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincroniza una subcategoría individual desde Valencia a Zenvy
     * @param int $idValencia
     * @return array [success, mensaje, id_zenvy]
     */
    public function sincronizarSubcategoria($idValencia)
    {
        try {
            // Verificar conectividad
            if (!$this->verificarConectividad()) {
                return [
                    'success' => false,
                    'mensaje' => 'No se puede conectar con la base de datos externa',
                ];
            }

            // Buscar subcategoría externa por ID
            $subcategoriaExterna = SubcategoriaExterna::where('id', $idValencia)->first();
            if (!$subcategoriaExterna) {
                return [
                    'success' => false,
                    'mensaje' => "No se encontró la subcategoría externa con ID: $idValencia",
                ];
            }

            // Buscar el mapeo de la categoría padre
            $mapeoCategoriaExterna = IdZenvyValencia::buscarPorValencia(
                $subcategoriaExterna->categoria_producto_id,
                self::TIPO_DATO_CATEGORIAS
            );
            if (!$mapeoCategoriaExterna) {
                return [
                    'success' => false,
                    'mensaje' => "No se encontró mapeo para la categoría externa ID: {$subcategoriaExterna->categoria_producto_id}",
                ];
            }
            $categoriaLocalId = $mapeoCategoriaExterna->id_zenvy;

            // Buscar si ya existe un mapeo para esta subcategoría de Valencia
            $mapeoExistente = IdZenvyValencia::buscarPorValencia($idValencia, self::TIPO_DATO_SUBCATEGORIAS);
            if ($mapeoExistente) {
                // Ya existe, actualizar si es necesario
                $subcategoriaLocal = Subcategoria::find($mapeoExistente->id_zenvy, ['id', 'nombre', 'categoria_id']);
                if ($subcategoriaLocal) {
                    $cambios = false;
                    if ($subcategoriaLocal->nombre !== $subcategoriaExterna->descripcion) {
                        $subcategoriaLocal->nombre = $subcategoriaExterna->descripcion;
                        $cambios = true;
                    }
                    if ($subcategoriaLocal->categoria_id !== $categoriaLocalId) {
                        $subcategoriaLocal->categoria_id = $categoriaLocalId;
                        $cambios = true;
                    }
                    if ($cambios) {
                        $subcategoriaLocal->save();
                        return [
                            'success' => true,
                            'mensaje' => 'Subcategoría actualizada',
                            'id_zenvy' => $subcategoriaLocal->id
                        ];
                    }
                    return [
                        'success' => true,
                        'mensaje' => 'Subcategoría ya sincronizada (sin cambios)',
                        'id_zenvy' => $subcategoriaLocal->id
                    ];
                } else {
                    return [
                        'success' => false,
                        'mensaje' => 'El mapeo existe pero la subcategoría local no fue encontrada',
                    ];
                }
            } else {
                // Crear nueva subcategoría
                $nuevaSubcategoria = Subcategoria::create([
                    'nombre' => $subcategoriaExterna->descripcion,
                    'categoria_id' => $categoriaLocalId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Crear mapeo
                IdZenvyValencia::crearMapeo($nuevaSubcategoria->id, $subcategoriaExterna->id, self::TIPO_DATO_SUBCATEGORIAS);
                return [
                    'success' => true,
                    'mensaje' => 'Subcategoría creada y mapeada',
                    'id_zenvy' => $nuevaSubcategoria->id
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error al sincronizar subcategoría individual: ' . $e->getMessage(), [
                'id_valencia' => $idValencia,
                'error' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'mensaje' => 'Error al sincronizar subcategoría: ' . $e->getMessage(),
            ];
        }
    }
}
