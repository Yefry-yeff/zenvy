<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sincronizar marcas cada 30 minutos
        $schedule->command('marcas:sincronizar')
                ->everyThirtyMinutes()
                ->runInBackground()
                ->withoutOverlapping()
                ->onFailure(function () {
                    Log::error('Fallo en sincronización automática de marcas');
                });

        // Forzar sincronización completa de marcas una vez al día a las 2:00 AM
        $schedule->command('marcas:sincronizar --force')
                ->dailyAt('02:00')
                ->runInBackground()
                ->withoutOverlapping();

        // Sincronización automática de productos en la madrugada (3:00 AM)
        $schedule->command('sincronizar:productos')
                ->dailyAt('03:00')
                ->runInBackground()
                ->withoutOverlapping()
                ->onFailure(function () {
                    Log::error('Fallo en sincronización automática de productos');
                })
                ->onSuccess(function () {
                    Log::info('Sincronización automática de productos completada exitosamente');
                });

        // Sincronización adicional de productos los domingos a las 1:00 AM (respaldo semanal)
        $schedule->command('sincronizar:productos')
                ->weeklyOn(0, '01:00') // Domingo a la 1:00 AM
                ->runInBackground()
                ->withoutOverlapping()
                ->onFailure(function () {
                    Log::error('Fallo en sincronización semanal de productos');
                });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
