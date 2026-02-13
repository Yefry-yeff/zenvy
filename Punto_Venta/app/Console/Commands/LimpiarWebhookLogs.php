<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Cache;

class LimpiarWebhookLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:clean-logs 
                            {--days=30 : Días de logs a mantener}
                            {--force : Forzar limpieza sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar logs antiguos de webhooks de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = (int) $this->option('days');
        $force = $this->option('force');

        // Contar logs a eliminar
        $totalAEliminar = WebhookLog::where('created_at', '<', now()->subDays($dias))->count();

        if ($totalAEliminar === 0) {
            $this->info('✅ No hay logs antiguos para eliminar.');
            return Command::SUCCESS;
        }

        $this->info("📊 Se encontraron {$totalAEliminar} logs con más de {$dias} días.");

        if (!$force) {
            if (!$this->confirm('¿Desea continuar con la eliminación?', true)) {
                $this->warn('⚠️ Operación cancelada.');
                return Command::CANCELLED;
            }
        }

        $this->info('🗑️ Eliminando logs antiguos...');
        
        $bar = $this->output->createProgressBar($totalAEliminar);
        $bar->start();

        // Eliminar en lotes para evitar timeout
        $eliminados = 0;
        do {
            $batch = WebhookLog::where('created_at', '<', now()->subDays($dias))
                              ->limit(1000)
                              ->delete();
            $eliminados += $batch;
            $bar->advance($batch);
        } while ($batch > 0);

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Se eliminaron {$eliminados} logs exitosamente.");

        // Limpiar cache de estadísticas
        $this->info('🧹 Limpiando cache de estadísticas...');
        Cache::forget('webhook_stats_total');
        Cache::forget('webhook_stats_exitosos');
        Cache::forget('webhook_stats_fallidos');
        Cache::forget('webhook_stats_ultima_hora');
        Cache::forget('webhook_stats_promedio_tiempo');

        $this->info('✅ Cache limpiado.');

        return Command::SUCCESS;
    }
}
