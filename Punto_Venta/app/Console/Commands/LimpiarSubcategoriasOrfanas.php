<?php

namespace App\Console\Commands;

use App\Services\SincronizacionSubcategoriasService;
use Illuminate\Console\Command;

class LimpiarSubcategoriasOrfanas extends Command
{
    protected $signature = 'subcategorias:limpiar-orfanas';
    protected $description = 'Limpiar mapeos de subcategorías órfanas (que ya no existen en Zenvy)';

    private $sincronizacionService;

    public function __construct(SincronizacionSubcategoriasService $sincronizacionService)
    {
        parent::__construct();
        $this->sincronizacionService = $sincronizacionService;
    }

    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de subcategorías órfanas...');

        try {
            $eliminados = $this->sincronizacionService->limpiarSubcategoriasOrfanas();
            
            $this->info("✅ Limpieza completada. Mapeos eliminados: $eliminados");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            return 1;
        }
    }
}