<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SincronizacionUnidadesService;

class SincronizarUnidades extends Command
{
    protected $signature = 'unidades:sincronizar 
                            {--forzar : Forzar sincronización ignorando cache}';
    
    protected $description = 'Sincroniza unidades de medida desde profac_app a db_zenvy';

    protected $sincronizacionService;

    public function __construct(SincronizacionUnidadesService $sincronizacionService)
    {
        parent::__construct();
        $this->sincronizacionService = $sincronizacionService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de unidades de medida desde profac_app a db_zenvy...');
        
        try {
            // Verificar conectividad
            if (!$this->sincronizacionService->verificarConectividad()) {
                $this->error('❌ No se puede conectar con la base de datos externa profac_app');
                return Command::FAILURE;
            }
            
            $this->info('✅ Conexión con base externa establecida');
            
            // Mostrar estadísticas iniciales
            $this->info('📊 Estado antes de la sincronización:');
            $this->mostrarEstadisticas();
            
            // Ejecutar sincronización
            if ($this->option('forzar')) {
                $this->info('🔄 Ejecutando sincronización forzada...');
                $resultado = $this->sincronizacionService->forzarSincronizacion();
            } else {
                $this->info('🔄 Ejecutando sincronización normal...');
                $resultado = $this->sincronizacionService->sincronizarUnidadesEnTiempoReal();
            }
            
            // Mostrar resultados
            $this->info('✅ Sincronización exitosa: ' . $resultado['nuevas'] . ' nuevas, ' . $resultado['actualizadas'] . ' actualizadas');
            
            $this->newLine();
            $this->info('📈 Resultados de la sincronización:');
            $headers = ['Métrica', 'Cantidad'];
            $rows = [
                ['Unidades Nuevas', $resultado['nuevas']],
                ['Unidades Actualizadas', $resultado['actualizadas']],
                ['Sin Cambios', $resultado['sin_cambios']],
                ['Total Procesadas', $resultado['total_procesadas']],
            ];
            $this->table($headers, $rows);
            
            // Mostrar estadísticas finales
            $this->newLine();
            $this->info('📊 Estado después de la sincronización:');
            $this->mostrarEstadisticas();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la sincronización: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function mostrarEstadisticas()
    {
        $estadisticas = $this->sincronizacionService->obtenerEstadisticas();
        
        $headers = ['Métrica', 'Valor'];
        $rows = [];
        foreach ($estadisticas as $metrica => $valor) {
            $rows[] = [$metrica, $valor];
        }
        
        $this->table($headers, $rows);
    }
}
