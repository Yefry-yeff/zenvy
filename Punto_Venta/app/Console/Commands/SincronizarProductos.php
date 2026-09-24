<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SincronizacionProductosService;
use Illuminate\Support\Facades\Log;

class SincronizarProductos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sincronizar:productos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza productos desde Valencia automáticamente';

    private $sincronizacionService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->sincronizacionService = app(SincronizacionProductosService::class);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Iniciando sincronización automática de productos...');
        
        try {
            $inicioTiempo = microtime(true);
            
            // Ejecutar sincronización completa
            $this->info('🔄 Ejecutando sincronización de todos los productos...');
            $resultado = $this->sincronizacionService->sincronizarTodosLosProductos();
            
            $tiempoEjecucion = round(microtime(true) - $inicioTiempo, 2);
            
            // Mostrar estadísticas
            $this->mostrarEstadisticas($resultado, $tiempoEjecucion);
            
            // Log para seguimiento
            Log::info('Sincronización automática de productos completada', [
                'estadisticas' => $resultado,
                'tiempo_ejecucion' => $tiempoEjecucion
            ]);
            
            $this->info('✅ Sincronización completada exitosamente!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la sincronización: ' . $e->getMessage());
            Log::error('Error en sincronización automática de productos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Mostrar estadísticas de sincronización
     */
    private function mostrarEstadisticas($resultado, $tiempoEjecucion)
    {
        $this->info('📊 Estadísticas de Sincronización:');
        $this->line('─────────────────────────────────');
        
        $this->line("🆕 Productos creados: " . ($resultado['creados'] ?? 0));
        $this->line("🔄 Productos actualizados: " . ($resultado['actualizados'] ?? 0));
        $this->line("✅ Productos sincronizados: " . ($resultado['sincronizados'] ?? 0));
        
        if (isset($resultado['errores']) && $resultado['errores'] > 0) {
            $this->line("❌ Errores: " . $resultado['errores']);
            
            // Mostrar mensajes de error si existen
            if (!empty($resultado['mensajes'])) {
                $this->line('');
                $this->line('� Detalles de errores:');
                foreach (array_slice($resultado['mensajes'], 0, 5) as $mensaje) {
                    $this->line("   • " . $mensaje);
                }
                if (count($resultado['mensajes']) > 5) {
                    $this->line("   ... y " . (count($resultado['mensajes']) - 5) . " errores más");
                }
            }
        }
        
        $this->line("⏱️  Tiempo de ejecución: {$tiempoEjecucion} segundos");
        $this->line('─────────────────────────────────');
    }
}