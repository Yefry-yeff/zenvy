<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SincronizacionMarcasService;

class SincronizarMarcas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marcas:sincronizar 
                           {--force : Forzar sincronización ignorando cache}
                           {--stats : Mostrar estadísticas de sincronización}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza marcas desde la base de datos externa profac_app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sincronizacionService = new SincronizacionMarcasService();

        $this->info('🔄 Iniciando sincronización de marcas desde profac_app a db_zenvy...');

        // Verificar conectividad
        $conectividad = $sincronizacionService->verificarConectividad();
        if (!$conectividad['status']) {
            $this->error('❌ Error de conectividad: ' . $conectividad['message']);
            return Command::FAILURE;
        }

        $this->info('✅ Conexión con base externa establecida');

        if ($this->option('stats')) {
            $this->mostrarEstadisticas($sincronizacionService);
            return Command::SUCCESS;
        }

        // Mostrar estadísticas antes
        $this->line('📊 Estado antes de la sincronización:');
        $this->mostrarEstadisticas($sincronizacionService);
        $this->line('');

        // Ejecutar sincronización
        if ($this->option('force')) {
            $this->info('🔄 Ejecutando sincronización forzada...');
            $resultado = $sincronizacionService->forzarSincronizacion();
        } else {
            $this->info('🔄 Ejecutando sincronización normal...');
            $resultado = $sincronizacionService->sincronizarMarcasEnTiempoReal();
        }

        if ($resultado['success']) {
            $this->info('✅ ' . $resultado['message']);
            
            // Mostrar estadísticas detalladas si están disponibles
            if (isset($resultado['estadisticas'])) {
                $stats = $resultado['estadisticas'];
                $this->line('');
                $this->info('📈 Resultados de la sincronización:');
                $this->table(
                    ['Métrica', 'Cantidad'],
                    [
                        ['Marcas Nuevas', $stats['nuevas'] ?? 0],
                        ['Marcas Actualizadas', $stats['actualizadas'] ?? 0],
                        ['Sin Cambios', $stats['sin_cambios'] ?? 0],
                        ['Total Procesadas', $stats['total_procesadas'] ?? 0],
                    ]
                );
            }
            
            // Mostrar estadísticas después
            $this->line('');
            $this->line('📊 Estado después de la sincronización:');
            $this->mostrarEstadisticas($sincronizacionService);
            
        } else {
            $this->error('❌ ' . $resultado['message']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function mostrarEstadisticas($sincronizacionService)
    {
        $estadisticas = $sincronizacionService->obtenerEstadisticas();

        $this->info('📊 Estadísticas de Sincronización:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Marcas Locales', $estadisticas['marcas_locales'] ?? 'N/A'],
                ['Marcas Externas', $estadisticas['marcas_externas'] ?? 'N/A'],
                ['Conectividad', $estadisticas['conectividad'] ? '✅ Activa' : '❌ Inactiva'],
                ['Estado Cache', $estadisticas['ultimo_cache'] ?? 'N/A'],
            ]
        );
    }
}
