<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;

class TimezoneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Forzar la zona horaria de Honduras/Tegucigalpa
        $timezone = config('app.timezone', 'America/Tegucigalpa');
        
        // Configurar PHP timezone
        date_default_timezone_set($timezone);
        
        // Configurar Carbon timezone (usado por Laravel)
        Carbon::setTestNow();
        Carbon::setLocale(config('app.locale', 'es'));
        
        // Asegurar que Carbon use la zona horaria correcta
        config(['app.timezone' => $timezone]);
        
        // Log para verificar en desarrollo (comentado para evitar spam en logs)
        // if (config('app.debug')) {
        //     logger()->info('Timezone configurado: ' . $timezone);
        //     logger()->info('PHP Timezone: ' . date_default_timezone_get());
        //     logger()->info('Carbon Timezone: ' . Carbon::now()->timezoneName);
        // }
    }
}