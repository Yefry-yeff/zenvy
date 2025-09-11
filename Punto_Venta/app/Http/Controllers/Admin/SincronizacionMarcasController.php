<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SincronizacionMarcasService;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SincronizacionMarcasController extends Controller
{
    protected $sincronizacionService;

    public function __construct(SincronizacionMarcasService $sincronizacionService)
    {
        $this->sincronizacionService = $sincronizacionService;
    }

    /**
     * Muestra el panel de sincronización
     */
    public function index()
    {
        $conectividad = $this->sincronizacionService->verificarConectividad();
        $estadisticas = $this->sincronizacionService->obtenerEstadisticas();
        
        // Obtener marcas recientes (últimas 20)
        $marcasRecientes = Marca::latest('updated_at')
                               ->take(20)
                               ->get();

        return view('admin.sincronizacion-marcas', compact(
            'conectividad',
            'estadisticas', 
            'marcasRecientes'
        ));
    }

    /**
     * Ejecuta sincronización normal (con cache)
     */
    public function sincronizacionNormal(Request $request)
    {
        try {
            $resultado = $this->sincronizacionService->sincronizarMarcasEnTiempoReal();
            
            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => $resultado
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fuerza sincronización (ignora cache)
     */
    public function forzarSincronizacion(Request $request)
    {
        try {
            $resultado = $this->sincronizacionService->forzarSincronizacion();
            
            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => $resultado
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización forzada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpia el cache de marcas
     */
    public function limpiarCache(Request $request)
    {
        try {
            Cache::forget('marcas_sincronizadas');
            Cache::forget('marcas_externas_directas');
            Cache::forget('count_marcas_externas');
            Cache::forget('profac_conectividad');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache limpiado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint para obtener estadísticas en tiempo real
     */
    public function estadisticas()
    {
        try {
            $estadisticas = $this->sincronizacionService->obtenerEstadisticas();
            $conectividad = $this->sincronizacionService->verificarConectividad();
            
            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas,
                'conectividad' => $conectividad
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para verificar el estado de la sincronización
     */
    public function estado()
    {
        try {
            $conectividad = $this->sincronizacionService->verificarConectividad();
            $ultimaSync = Cache::get('marcas_sincronizadas');
            
            return response()->json([
                'conectividad' => $conectividad['status'],
                'mensaje_conectividad' => $conectividad['message'],
                'ultima_sincronizacion' => $ultimaSync ? 'Activa' : 'Inactiva',
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al verificar estado: ' . $e->getMessage()
            ], 500);
        }
    }
}
