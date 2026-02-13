<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ApiClient;
use App\Models\ApiLog;

class VerificarModuloApis extends Command
{
    protected $signature = 'apis:verificar {--seed : Crear datos de prueba}';
    protected $description = 'Verifica el estado del módulo de APIs y Webhooks';

    public function handle()
    {
        $this->info('🔍 Verificando módulo de APIs y Webhooks...');
        $this->newLine();

        // 1. Verificar tablas
        $this->info('1️⃣ Verificando tablas de base de datos:');
        $tablas = ['api_clients', 'api_logs'];
        $tablasExistentes = 0;

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                $count = DB::table($tabla)->count();
                $this->line("   ✅ {$tabla} - {$count} registros");
                $tablasExistentes++;
            } else {
                $this->error("   ❌ {$tabla} - NO EXISTE");
            }
        }

        $this->newLine();

        if ($tablasExistentes < count($tablas)) {
            $this->warn('⚠️  Faltan tablas. Ejecuta: php artisan migrate');
            return 1;
        }

        // 2. Verificar modelos
        $this->info('2️⃣ Verificando modelos:');
        
        try {
            $apiClientsCount = ApiClient::count();
            $this->line("   ✅ ApiClient - {$apiClientsCount} clientes");
        } catch (\Exception $e) {
            $this->error("   ❌ ApiClient - Error: " . $e->getMessage());
        }

        try {
            $apiLogsCount = ApiLog::count();
            $this->line("   ✅ ApiLog - {$apiLogsCount} logs");
        } catch (\Exception $e) {
            $this->error("   ❌ ApiLog - Error: " . $e->getMessage());
        }

        $this->newLine();

        // 3. Verificar configuración
        $this->info('3️⃣ Verificando configuración .env:');
        $configs = [
            'ZENVY_WEBHOOK_URL' => env('ZENVY_WEBHOOK_URL'),
            'ZENVY_WEBHOOK_TOKEN' => env('ZENVY_WEBHOOK_TOKEN'),
            'WEBHOOK_ENABLED' => config('app.webhook_enabled'),
            'WEBHOOK_TIMEOUT' => config('app.webhook_timeout'),
            'WEBHOOK_RETRY_ATTEMPTS' => config('app.webhook_retry_attempts'),
        ];

        foreach ($configs as $key => $value) {
            if ($value) {
                $displayValue = (strlen($value) > 30) ? substr($value, 0, 30) . '...' : $value;
                $this->line("   ✅ {$key} = {$displayValue}");
            } else {
                $this->warn("   ⚠️  {$key} - No configurado");
            }
        }

        $this->newLine();

        // 4. Crear datos de prueba si se solicita
        if ($this->option('seed')) {
            $this->info('4️⃣ Creando datos de prueba:');
            
            // Crear API Client de prueba
            if (ApiClient::where('name', 'TEST_CLIENT')->doesntExist()) {
                $apiKey = ApiClient::generateApiKey();
                $apiSecret = ApiClient::generateApiSecret();
                
                $client = ApiClient::create([
                    'name' => 'TEST_CLIENT',
                    'api_key' => $apiKey,
                    'api_secret' => hash('sha256', $apiSecret),
                    'is_active' => true,
                    'ip_whitelist' => null,
                    'rate_limit_per_minute' => 60,
                ]);
                
                $this->line("   ✅ API Client creado:");
                $this->line("      API Key: {$apiKey}");
                $this->line("      API Secret: {$apiSecret}");
                $this->warn("   ⚠️  GUARDA ESTAS CREDENCIALES, no se mostrarán de nuevo!");
            } else {
                $this->line("   ℹ️  API Client de prueba ya existe");
            }

            // Crear logs de prueba
            if (ApiLog::count() < 5) {
                $testClient = ApiClient::where('name', 'TEST_CLIENT')->first();
                
                for ($i = 1; $i <= 5; $i++) {
                    ApiLog::create([
                        'client_id' => $testClient ? $testClient->id : null,
                        'method' => $i % 2 == 0 ? 'GET' : 'POST',
                        'endpoint' => "/api/test/endpoint/{$i}",
                        'request_body' => ['test' => 'data', 'index' => $i],
                        'response_status' => $i % 2 == 0 ? 200 : 400,
                        'response_body' => ['status' => $i % 2 == 0 ? 'success' : 'error'],
                        'ip_address' => '192.168.1.' . $i,
                        'user_agent' => 'TestAgent/1.0',
                        'duration_ms' => 100 + ($i * 50),
                    ]);
                }
                $this->line("   ✅ 5 logs de prueba creados");
            } else {
                $this->line("   ℹ️  Ya existen logs en el sistema");
            }

            $this->newLine();
        }

        // Resumen final
        $this->info('✅ Verificación completada');
        $this->newLine();
        $this->line('📊 Resumen:');
        $this->table(
            ['Componente', 'Estado', 'Datos'],
            [
                ['Tabla api_clients', '✅', ApiClient::count() . ' clientes'],
                ['Tabla api_logs', '✅', ApiLog::count() . ' logs'],
                ['Modelos', '✅', 'Funcionando'],
                ['Configuración', env('ZENVY_WEBHOOK_URL') ? '✅' : '⚠️', env('ZENVY_WEBHOOK_URL') ? 'Configurado' : 'Pendiente'],
            ]
        );

        $this->newLine();
        
        if (ApiClient::count() === 0) {
            $this->warn('💡 Sugerencia: Ejecuta "php artisan apis:verificar --seed" para crear datos de prueba');
        }

        return 0;
    }
}
