<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:check';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verificar la configuración de zona horaria';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE ZONA HORARIA ===');
        $this->line('');
        
        // Configuración de la aplicación
        $this->info('📱 Configuración de la aplicación:');
        $this->line('APP_TIMEZONE: ' . config('app.timezone'));
        $this->line('');
        
        // PHP timezone
        $this->info('🐘 PHP timezone:');
        $this->line('date_default_timezone_get(): ' . date_default_timezone_get());
        $this->line('date(): ' . date('Y-m-d H:i:s'));
        $this->line('');
        
        // Carbon timezone
        $this->info('💎 Carbon timezone:');
        $carbon = Carbon::now();
        $this->line('Carbon::now()->timezone: ' . $carbon->timezone->getName());
        $this->line('Carbon::now(): ' . $carbon->format('Y-m-d H:i:s'));
        $this->line('');
        
        // Carbon en zona horaria específica de Honduras
        $this->info('🇭🇳 Carbon Honduras específico:');
        $hondurasCarbon = Carbon::now('America/Tegucigalpa');
        $this->line('Carbon::now("America/Tegucigalpa"): ' . $hondurasCarbon->format('Y-m-d H:i:s'));
        $this->line('Timezone: ' . $hondurasCarbon->timezone->getName());
        $this->line('');
        
        // Helpers personalizados
        $this->info('🔧 Helpers personalizados:');
        if (function_exists('hondurasNow')) {
            $this->line('hondurasNow(): ' . hondurasNow()->format('Y-m-d H:i:s'));
            $this->line('formatDateTimeHonduras(now()): ' . formatDateTimeHonduras(now()));
        } else {
            $this->error('Los helpers de zona horaria no están cargados');
        }
        $this->line('');
        
        // Diferencia horaria
        $utc = Carbon::now('UTC');
        $honduras = Carbon::now('America/Tegucigalpa');
        $diffHours = $utc->diffInHours($honduras, false);
        
        $this->info('⏰ Diferencia horaria:');
        $this->line('UTC: ' . $utc->format('Y-m-d H:i:s'));
        $this->line('Honduras: ' . $honduras->format('Y-m-d H:i:s'));
        $this->line('Diferencia: ' . $diffHours . ' horas');
        $this->line('');
        
        if (abs($diffHours) == 6) {
            $this->success('✅ Configuración correcta - Honduras está 6 horas detrás de UTC');
        } else {
            $this->error('❌ Posible problema con la configuración de zona horaria');
        }
        
        return 0;
    }
}