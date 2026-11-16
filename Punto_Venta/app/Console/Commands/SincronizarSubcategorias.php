<?php

namespace App\Console\Commands;

use App\Services\SincronizacionSubcategoriasService;
use Illuminate\Console\Command;

class SincronizarSubcategorias extends Command
{
    protected $signature = 'subcategorias:sincronizar';
    protected $description = 'Sincronizar subcategorías desde profac_app a db_zenvy';

    private $sincronizacionService;

    public function __construct(SincronizacionSubcategoriasService $sincronizacionService)
    {
        parent::__construct();
        $this->sincronizacionService = $sincronizacionService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de subcategorías desde profac_app a db_zenvy...');

        try {
            // Verificar conectividad
            if (!$this->sincronizacionService->verificarConectividad()) {
                $this->error('❌ No se puede conectar con la base de datos externa');
                return 1;
            }
            
            $this->info('✅ Conexión con base externa establecida');

            // Mostrar estadísticas antes
            $this->info('📊 Estado antes de la sincronización:');
            $this->mostrarEstadisticas();

            // Ejecutar sincronización
            $this->info('🔄 Ejecutando sincronización normal...');
            $resultado = $this->sincronizacionService->forzarSincronizacion();

            $this->info('✅ Sincronización exitosa: ' . $resultado['nuevas'] . ' nuevas, ' . $resultado['actualizadas'] . ' actualizadas');

            // Mostrar resultados detallados
            $this->info('📈 Resultados de la sincronización:');
            $headers = ['Métrica', 'Cantidad'];
            $rows = [
                ['Subcategorías Nuevas', $resultado['nuevas']],
                ['Subcategorías Actualizadas', $resultado['actualizadas']],
                ['Sin Cambios', $resultado['sin_cambios']],
                ['Errores', $resultado['errores']],
                ['Total Procesadas', array_sum($resultado)]
            ];
            $this->table($headers, $rows);

            // Mostrar estadísticas después
            $this->info('📊 Estado después de la sincronización:');
            $this->mostrarEstadisticas();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la sincronización: ' . $e->getMessage());
            return 1;
        }
    }

    private function mostrarEstadisticas()
    {
        $stats = $this->sincronizacionService->obtenerEstadisticas();
        
        $headers = ['Métrica', 'Valor'];
        $rows = [];
        foreach ($stats as $metrica => $valor) {
            $rows[] = [$metrica, $valor];
        }
        
        $this->table($headers, $rows);
    }
}