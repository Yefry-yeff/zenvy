<?php

namespace App\Console\Commands;

use App\Services\SincronizacionCategoriasService;
use Illuminate\Console\Command;

class CrearMapeosRetroactivosCategorias extends Command
{
    protected $signature = 'categorias:mapeos-retroactivos';
    protected $description = 'Crear mapeos retroactivos para categorías existentes con profac_app';

    private $sincronizacionService;

    public function __construct(SincronizacionCategoriasService $sincronizacionService)
    {
        parent::__construct();
        $this->sincronizacionService = $sincronizacionService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando creación de mapeos retroactivos para categorías...');

        try {
            // Verificar conectividad
            if (!$this->sincronizacionService->verificarConectividad()) {
                $this->error('❌ No se puede conectar con la base de datos externa');
                return 1;
            }
            
            $this->info('✅ Conexión con base externa establecida');

            // Mostrar estadísticas antes
            $this->info('📊 Estado antes de crear mapeos:');
            $this->mostrarEstadisticas();

            // Crear mapeos retroactivos
            $mapeosCreados = $this->sincronizacionService->crearMapeosRetroactivos();
            
            $this->info('✅ Mapeos retroactivos creados: ' . $mapeosCreados);

            // Mostrar estadísticas después
            $this->info('📊 Estado después de crear mapeos:');
            $this->mostrarEstadisticas();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error creando mapeos retroactivos: ' . $e->getMessage());
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
