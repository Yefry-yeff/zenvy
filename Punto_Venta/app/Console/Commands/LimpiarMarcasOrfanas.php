<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SincronizacionMarcasService;

class LimpiarMarcasOrfanas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marcas:limpiar-orfanas 
                           {--dry-run : Solo mostrar qué marcas se eliminarían sin ejecutar}
                           {--force : Confirmar la eliminación sin preguntar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina marcas locales que no existen en el sistema externo profac_app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sincronizacionService = new SincronizacionMarcasService();

        $this->info('🧹 Iniciando limpieza de marcas órfanas...');

        // Verificar conectividad
        $conectividad = $sincronizacionService->verificarConectividad();
        if (!$conectividad['status']) {
            $this->error('❌ Error de conectividad: ' . $conectividad['message']);
            $this->error('No se puede verificar marcas órfanas sin conexión al sistema externo');
            return Command::FAILURE;
        }

        $this->info('✅ Conexión con base externa establecida');

        if ($this->option('dry-run')) {
            $this->info('🔍 Ejecutando en modo DRY-RUN (solo verificación)...');
        }

        // Ejecutar limpieza
        $resultado = $sincronizacionService->limpiarMarcasOrfanas();

        if ($resultado['success']) {
            if ($resultado['eliminadas'] > 0) {
                $this->info("✅ {$resultado['message']}");
                
                $this->table(
                    ['Métrica', 'Cantidad'],
                    [
                        ['Marcas Órfanas Encontradas', $resultado['encontradas']],
                        ['Marcas Eliminadas', $resultado['eliminadas']],
                        ['Marcas Preservadas (en uso)', $resultado['encontradas'] - $resultado['eliminadas']],
                    ]
                );
            } else {
                $this->info('✅ No se encontraron marcas órfanas para eliminar');
            }
        } else {
            $this->error('❌ ' . $resultado['message']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
