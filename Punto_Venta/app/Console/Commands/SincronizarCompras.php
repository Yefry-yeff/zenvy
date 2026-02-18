<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SincronizacionComprasService;
use Illuminate\Support\Facades\Log;

class SincronizarCompras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sincronizar:compras 
                           {--limite=100 : Número máximo de compras a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar compras desde Valencia a Zenvy - solo nuevas compras';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== Iniciando Sincronización de Compras Valencia ===');
        $this->info('Fecha/Hora: ' . now()->format('Y-m-d H:i:s'));

        try {
            $sincronizacionService = app(SincronizacionComprasService::class);
            
            $this->info('Conectando con Valencia...');
            $resultado = $sincronizacionService->sincronizarComprasValencia();

            if ($resultado['success']) {
                $estadisticas = $resultado['estadisticas'];
                
                $this->info('✅ Sincronización completada exitosamente');
                $this->table(
                    ['Métrica', 'Valor'],
                    [
                        ['Compras nuevas', $estadisticas['compras_nuevas']],
                        ['Productos sincronizados', $estadisticas['productos_sincronizados']],
                        ['Productos migrados', $estadisticas['productos_migrados'] ?? 0],
                        ['Total procesadas', $estadisticas['total_procesadas']],
                        ['Errores', $estadisticas['errores']]
                    ]
                );

                // Log del resultado
                Log::info('Sincronización automática de compras completada', $estadisticas);

                if ($estadisticas['compras_nuevas'] > 0) {
                    $mensaje = "🎉 Se sincronizaron {$estadisticas['compras_nuevas']} compras nuevas con {$estadisticas['productos_sincronizados']} productos.";
                    if (($estadisticas['productos_migrados'] ?? 0) > 0) {
                        $mensaje .= " Se migraron {$estadisticas['productos_migrados']} productos nuevos desde Valencia.";
                    }
                    $this->info($mensaje);
                } else {
                    $this->info("ℹ️  No hay nuevas compras para sincronizar.");
                }

                return Command::SUCCESS;
                
            } else {
                $this->error('❌ Error en la sincronización: ' . $resultado['mensaje']);
                
                // Mostrar productos fallidos si existen
                if (!empty($resultado['estadisticas']['productos_fallidos'])) {
                    $this->error('Productos que no se pudieron migrar:');
                    $productosTable = [];
                    foreach ($resultado['estadisticas']['productos_fallidos'] as $producto) {
                        $productosTable[] = [
                            $producto['nombre'],
                            $producto['id_valencia'],
                            substr($producto['error'], 0, 80) // Limitar longitud del error
                        ];
                    }
                    $this->table(['Nombre', 'ID Valencia', 'Error'], $productosTable);
                }
                
                Log::error('Error en sincronización automática de compras: ' . $resultado['mensaje']);
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error inesperado: ' . $e->getMessage());
            Log::error('Error inesperado en sincronización de compras: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}