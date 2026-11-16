<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\CategoriaExterna;
use App\Models\IdZenvyValencia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SincronizacionCategoriasService
{
    private const CACHE_KEY = 'categorias_sincronizadas';
    private const CACHE_DURATION = 300; // 5 minutos
    private const TIPO_DATO_CATEGORIAS = 3; // Tipo para categorías en Valencia

    public function sincronizarCategoriasEnTiempoReal()
    {
        // Si las categorías están en caché y son recientes, no sincronizar
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
            Log::error('Error en sincronización forzada de categorías: ' . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerCategoriasDirectas()
    {
        // Intentar sincronización en tiempo real primero
        $this->sincronizarCategoriasEnTiempoReal();
        
        // Devolver todas las categorías locales
        return Categoria::orderBy('descripcion')->get(['id', 'descripcion']);
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

            // Obtener categorías desde profac_app
            $categoriasExternas = CategoriaExterna::all();
            
            Log::info("Sincronizando categorías. Total externas: " . $categoriasExternas->count());

            foreach ($categoriasExternas as $categoriaExterna) {
                $resultado = $this->procesarCategoriaIndividual($categoriaExterna);
                
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

            Log::info('Sincronización de categorías completada', $stats);
            return $stats;

        } catch (Exception $e) {
            Log::error('Error en sincronización de categorías: ' . $e->getMessage());
            throw $e;
        }
    }

    private function procesarCategoriaIndividual($categoriaExterna)
    {
        try {
            // Buscar si ya existe un mapeo para esta categoría de Valencia
            $mapeoExistente = IdZenvyValencia::buscarPorValencia($categoriaExterna->id, self::TIPO_DATO_CATEGORIAS);
            
            if ($mapeoExistente) {
                // Actualizar categoría existente
                $categoriaLocal = Categoria::find($mapeoExistente->id_zenvy);
                if ($categoriaLocal) {
                    $cambios = false;
                    if ($categoriaLocal->nombre !== $categoriaExterna->descripcion) {
                        $categoriaLocal->nombre = $categoriaExterna->descripcion;
                        $cambios = true;
                    }
                    
                    if ($cambios) {
                        $categoriaLocal->save();
                        return 'actualizada';
                    }
                    return 'sin_cambios';
                }
            } else {
                // Crear nueva categoría
                $nuevaCategoria = Categoria::create([
                    'nombre' => $categoriaExterna->descripcion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Crear mapeo
                IdZenvyValencia::crearMapeo($nuevaCategoria->id, $categoriaExterna->id, self::TIPO_DATO_CATEGORIAS);
                
                return 'nueva';
            }
        } catch (\Exception $e) {
            Log::error('Error procesando categoría individual: ' . $e->getMessage(), [
                'categoria_externa' => $categoriaExterna,
                'error' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return 'error';
    }

    public function verificarConectividad()
    {
        try {
            CategoriaExterna::first();
            return true;
        } catch (Exception $e) {
            Log::error('Error de conectividad con base externa: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $categoriasLocales = Categoria::count();
            $categoriasExternas = $this->verificarConectividad() ? CategoriaExterna::count() : 0;
            $conectividad = $this->verificarConectividad();
            $estadoCache = Cache::has(self::CACHE_KEY) ? 'Activo' : 'Inactivo';

            return [
                'Categorías Locales' => $categoriasLocales,
                'Categorías Externas' => $categoriasExternas,
                'Conectividad' => $conectividad ? '✅ Activa' : '❌ Inactiva',
                'Estado Cache' => $estadoCache
            ];
        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas de categorías: ' . $e->getMessage());
            return [
                'Error' => $e->getMessage()
            ];
        }
    }

    public function limpiarCategoriasOrfanas()
    {
        try {
            // Buscar mapeos que apuntan a categorías que ya no existen en Zenvy
            $mapeosOrfanos = IdZenvyValencia::where('tipo_dato_migrado_id', self::TIPO_DATO_CATEGORIAS)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('categoria')
                          ->whereRaw('categoria.id = id_zenvy_valencia.id_zenvy');
                })
                ->get();

            $eliminados = 0;
            foreach ($mapeosOrfanos as $mapeo) {
                $mapeo->delete();
                $eliminados++;
            }

            Log::info("Limpieza de categorías órfanas completada. Eliminados: $eliminados");
            return $eliminados;

        } catch (Exception $e) {
            Log::error('Error limpiando categorías órfanas: ' . $e->getMessage());
            throw $e;
        }
    }

    public function crearMapeosRetroactivos()
    {
        try {
            if (!$this->verificarConectividad()) {
                throw new Exception('No se puede conectar con la base de datos externa');
            }

            $categoriasLocales = Categoria::all();
            $categoriasExternas = CategoriaExterna::all();
            
            $mapeosCreados = 0;
            
            foreach ($categoriasLocales as $categoriaLocal) {
                // Verificar si ya tiene mapeo
                $mapeoExistente = IdZenvyValencia::where('id_zenvy', $categoriaLocal->id)
                                                ->where('tipo_dato_migrado_id', self::TIPO_DATO_CATEGORIAS)
                                                ->first();
                
                if (!$mapeoExistente) {
                    // Buscar categoría externa con descripción similar
                    $categoriaExterna = $categoriasExternas->firstWhere('descripcion', $categoriaLocal->nombre);
                    
                    if ($categoriaExterna) {
                        // Verificar que el ID externo no esté ya mapeado
                        $mapeoExternoExistente = IdZenvyValencia::buscarPorValencia(
                            $categoriaExterna->id, 
                            self::TIPO_DATO_CATEGORIAS
                        );
                        
                        if (!$mapeoExternoExistente) {
                            IdZenvyValencia::crearMapeo(
                                $categoriaLocal->id, 
                                $categoriaExterna->id, 
                                self::TIPO_DATO_CATEGORIAS
                            );
                            $mapeosCreados++;
                        }
                    }
                }
            }
            
            Log::info("Mapeos retroactivos de categorías creados: $mapeosCreados");
            return $mapeosCreados;
            
        } catch (Exception $e) {
            Log::error('Error creando mapeos retroactivos de categorías: ' . $e->getMessage());
            throw $e;
        }
    }
}
