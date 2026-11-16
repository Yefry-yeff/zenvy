<?php

namespace App\Console\Commands;

use App\Services\SincronizacionCategoriasService;
use Illuminate\Console\Command;

class LimpiarCategoriasOrfanas extends Command
{
    protected $signature = 'categorias:limpiar-orfanas';
    protected $description = 'Limpiar mapeos de categorías órfanas (que ya no existen en Zenvy)';

    private $sincronizacionService;

    public function __construct(SincronizacionCategoriasService $sincronizacionService)
    {
        parent::__construct();
        $this->sincronizacionService = $sincronizacionService;
    }

    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de categorías órfanas...');

        try {
            $eliminados = $this->sincronizacionService->limpiarCategoriasOrfanas();
            
            $this->info("✅ Limpieza completada. Mapeos eliminados: $eliminados");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            return 1;
        }
    }
}
