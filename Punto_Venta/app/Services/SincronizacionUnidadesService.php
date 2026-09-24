<?php

namespace App\Services;

use App\Models\UnidadMedida;
use App\Models\UnidadMedidaExterna;
use App\Models\IdZenvyValencia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class SincronizacionUnidadesService
{
    private const CACHE_KEY = 'unidades_sincronizadas';
    private const CACHE_DURATION = 300; // 5 minutos
    private const TIPO_DATO_UNIDADES = 5; // Tipo para unidades de medida en Valencia

    public function sincronizarUnidadesEnTiempoReal()
    {
        // Verificar si hay sincronización reciente en cache
        if (Cache::has(self::CACHE_KEY)) {
            Log::info('Usando cache para unidades de medida');
            return Cache::get(self::CACHE_KEY);
        }

        return $this->forzarSincronizacion();
    }

    public function forzarSincronizacion()
    {
        try {
            $resultado = $this->sincronizarDesdeBaseExterna();
            
            // Guardar en cache
            Cache::put(self::CACHE_KEY, $resultado, self::CACHE_DURATION);
            
            return $resultado;
        } catch (Exception $e) {
            Log::error('Error en sincronización de unidades: ' . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerUnidadesDirectas()
    {
        // Intentar sincronización silenciosa
        try {
            $this->sincronizarUnidadesEnTiempoReal();
        } catch (Exception $e) {
            Log::warning('No se pudo sincronizar unidades, usando datos locales: ' . $e->getMessage());
        }

        // Devolver todas las unidades locales
        return UnidadMedida::orderBy('nombre')->get(['id', 'unidad', 'nombre', 'simbolo']);
    }

    private function sincronizarDesdeBaseExterna()
    {
        if (!$this->verificarConectividad()) {
            throw new Exception('No se puede conectar a la base externa profac_app');
        }

        $unidadesExternas = UnidadMedidaExterna::all();
        $nuevas = 0;
        $actualizadas = 0;
        $sinCambios = 0;

        Log::info('Iniciando sincronización de ' . count($unidadesExternas) . ' unidades desde profac_app');

        foreach ($unidadesExternas as $unidadExterna) {
            $resultado = $this->procesarUnidadIndividual($unidadExterna);
            
            switch ($resultado) {
                case 'nueva':
                    $nuevas++;
                    break;
                case 'actualizada':
                    $actualizadas++;
                    break;
                case 'sin_cambios':
                    $sinCambios++;
                    break;
            }
        }

        $resultado = [
            'nuevas' => $nuevas,
            'actualizadas' => $actualizadas,
            'sin_cambios' => $sinCambios,
            'total_procesadas' => count($unidadesExternas),
            'timestamp' => now()
        ];

        Log::info('Sincronización de unidades completada: ' . json_encode($resultado));
        return $resultado;
    }

    private function procesarUnidadIndividual($unidadExterna)
    {
        try {
            // Buscar si ya existe mapeo
            $mapeo = IdZenvyValencia::buscarPorValencia($unidadExterna->id, self::TIPO_DATO_UNIDADES);
            
            if ($mapeo) {
                // Unidad ya existe, verificar si necesita actualización
                $unidadLocal = UnidadMedida::find($mapeo->id_zenvy);
                
                if ($unidadLocal) {
                    if ($this->necesitaActualizacion($unidadLocal, $unidadExterna)) {
                        $this->actualizarUnidadLocal($unidadLocal, $unidadExterna);
                        return 'actualizada';
                    }
                    return 'sin_cambios';
                } else {
                    // El mapeo apunta a una unidad que no existe, eliminar mapeo y crear nueva
                    $mapeo->delete();
                    return $this->crearNuevaUnidad($unidadExterna);
                }
            } else {
                // Verificar si existe una unidad con el mismo símbolo (posible duplicado)
                $unidadExistente = UnidadMedida::where('simbolo', $unidadExterna->simbolo)->first();
                
                if ($unidadExistente) {
                    // Crear mapeo para la unidad existente
                    IdZenvyValencia::crearMapeo(
                        $unidadExistente->id,
                        $unidadExterna->id,
                        self::TIPO_DATO_UNIDADES
                    );
                    
                    if ($this->necesitaActualizacion($unidadExistente, $unidadExterna)) {
                        $this->actualizarUnidadLocal($unidadExistente, $unidadExterna);
                        return 'actualizada';
                    }
                    return 'sin_cambios';
                } else {
                    // Crear nueva unidad
                    return $this->crearNuevaUnidad($unidadExterna);
                }
            }
        } catch (Exception $e) {
            Log::error('Error procesando unidad ID ' . $unidadExterna->id . ': ' . $e->getMessage());
            return 'error';
        }
    }

    private function crearNuevaUnidad($unidadExterna)
    {
        try {
            DB::beginTransaction();
            
            $nuevaUnidad = UnidadMedida::create([
                'unidad' => $unidadExterna->unidad,
                'nombre' => $unidadExterna->nombre,
                'simbolo' => $unidadExterna->simbolo,
                'created_at' => $unidadExterna->created_at,
                'updated_at' => $unidadExterna->updated_at
            ]);
            
            // Crear mapeo
            IdZenvyValencia::crearMapeo(
                $nuevaUnidad->id,
                $unidadExterna->id,
                self::TIPO_DATO_UNIDADES
            );
            
            DB::commit();
            Log::info('Nueva unidad creada: ' . $nuevaUnidad->nombre . ' (ID: ' . $nuevaUnidad->id . ')');
            return 'nueva';
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creando nueva unidad: ' . $e->getMessage());
            return 'error';
        }
    }

    private function actualizarUnidadLocal($unidadLocal, $unidadExterna)
    {
        $unidadLocal->update([
            'unidad' => $unidadExterna->unidad,
            'nombre' => $unidadExterna->nombre,
            'simbolo' => $unidadExterna->simbolo,
            'updated_at' => $unidadExterna->updated_at
        ]);
        
        Log::info('Unidad actualizada: ' . $unidadLocal->nombre . ' (ID: ' . $unidadLocal->id . ')');
    }

    private function necesitaActualizacion($unidadLocal, $unidadExterna)
    {
        return $unidadLocal->unidad != $unidadExterna->unidad ||
               $unidadLocal->nombre != $unidadExterna->nombre ||
               $unidadLocal->simbolo != $unidadExterna->simbolo ||
               $unidadLocal->updated_at->lt($unidadExterna->updated_at);
    }

    public function verificarConectividad()
    {
        try {
            UnidadMedidaExterna::limit(1)->get();
            return true;
        } catch (Exception $e) {
            Log::error('Error de conectividad con profac_app para unidades: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $unidadesLocales = UnidadMedida::count();
            $unidadesExternas = $this->verificarConectividad() ? UnidadMedidaExterna::count() : 0;
            $conectividad = $this->verificarConectividad();
            $estadoCache = Cache::has(self::CACHE_KEY) ? 'Activo' : 'Inactivo';
            
            return [
                'Unidades Locales' => $unidadesLocales,
                'Unidades Externas' => $unidadesExternas,
                'Conectividad' => $conectividad ? '✅ Activa' : '❌ Inactiva',
                'Estado Cache' => $estadoCache
            ];
        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas de unidades: ' . $e->getMessage());
            return [
                'Error' => 'No se pudieron obtener las estadísticas'
            ];
        }
    }

    public function limpiarUnidadesOrfanas()
    {
        try {
            // Encontrar mapeos que apuntan a unidades inexistentes
            $mapeosOrfanos = IdZenvyValencia::where('tipo_dato_migrado_id', self::TIPO_DATO_UNIDADES)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('unidad_medida')
                          ->whereRaw('unidad_medida.id = id_zenvy_valencia.id_zenvy');
                })
                ->get();

            $eliminados = 0;
            foreach ($mapeosOrfanos as $mapeo) {
                $mapeo->delete();
                $eliminados++;
            }

            Log::info("Limpieza de unidades: {$eliminados} mapeos órfanos eliminados");
            return ['eliminados' => $eliminados];
            
        } catch (Exception $e) {
            Log::error('Error limpiando unidades órfanas: ' . $e->getMessage());
            throw $e;
        }
    }

    public function crearMapeosRetroactivos()
    {
        try {
            if (!$this->verificarConectividad()) {
                throw new Exception('No se puede conectar a la base externa');
            }

            $unidadesExternas = UnidadMedidaExterna::all();
            $mapeosCreados = 0;

            Log::info('Iniciando creación de mapeos retroactivos para unidades');

            foreach ($unidadesExternas as $unidadExterna) {
                // Buscar unidad local por símbolo
                $unidadLocal = UnidadMedida::where('simbolo', $unidadExterna->simbolo)->first();
                
                if ($unidadLocal) {
                    // Verificar si ya existe mapeo
                    $mapeoExistente = IdZenvyValencia::where('id_zenvy', $unidadLocal->id)
                        ->where('id_valencia', $unidadExterna->id)
                        ->where('tipo_dato_migrado_id', self::TIPO_DATO_UNIDADES)
                        ->first();
                    
                    if (!$mapeoExistente) {
                        IdZenvyValencia::crearMapeo(
                            $unidadLocal->id,
                            $unidadExterna->id,
                            self::TIPO_DATO_UNIDADES
                        );
                        $mapeosCreados++;
                        Log::info("Mapeo creado: Unidad local {$unidadLocal->id} -> Valencia {$unidadExterna->id}");
                    }
                }
            }

            Log::info("Mapeos retroactivos completados: {$mapeosCreados} mapeos creados");
            return ['mapeos_creados' => $mapeosCreados];
            
        } catch (Exception $e) {
            Log::error('Error creando mapeos retroactivos para unidades: ' . $e->getMessage());
            throw $e;
        }
    }
}
