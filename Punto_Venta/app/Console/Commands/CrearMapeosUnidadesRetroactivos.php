<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SincronizacionUnidadesService;

class CrearMapeosUnidadesRetroactivos extends Command
{
    protected $signature = 'unidades:mapeos-retroactivos';
    
    protected $description = 'Crea mapeos retroactivos para unidades existentes entre db_zenvy y profac_app';

    protected $sincronizacionService;

    public function __construct(SincronizacionUnidadesService $sincronizacionService)
    {
        parent::__construct();
        $this->sincronizacionService = $sincronizacionService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando creación de mapeos retroactivos para unidades de medida...');
        
        try {
            // Verificar conectividad
            if (!$this->sincronizacionService->verificarConectividad()) {
                $this->error('❌ No se puede conectar con la base de datos externa profac_app');
                return Command::FAILURE;
            }
            
            $this->info('✅ Conexión con base externa establecida');
            
            // Mostrar estadísticas iniciales
            $this->info('📊 Estado antes de crear mapeos:');
            $this->mostrarEstadisticas();
            
            // Crear mapeos retroactivos
            $resultado = $this->sincronizacionService->crearMapeosRetroactivos();
            
            $this->info('✅ Mapeos retroactivos creados: ' . $resultado['mapeos_creados']);
            
            // Mostrar estadísticas finales
            $this->newLine();
            $this->info('📊 Estado después de crear mapeos:');
            $this->mostrarEstadisticas();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Error creando mapeos retroactivos: ' . $e->getMessage());
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
