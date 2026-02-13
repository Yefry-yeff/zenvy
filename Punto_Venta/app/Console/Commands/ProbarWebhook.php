<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProbarWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:test 
                            {--evento=test.conexion : Nombre del evento a enviar}
                            {--url= : URL del webhook (opcional, usa la configurada)}
                            {--token= : Token de autenticación (opcional, usa el configurado)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar conexión con el webhook configurado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $webhookUrl = $this->option('url') ?: config('app.webhook_url');
        $webhookToken = $this->option('token') ?: config('app.webhook_token');
        $evento = $this->option('evento');

        if (empty($webhookUrl)) {
            $this->error('❌ No hay URL de webhook configurada.');
            $this->info('💡 Configura WEBHOOK_URL en tu archivo .env o usa la opción --url');
            return Command::FAILURE;
        }

        if (empty($webhookToken)) {
            $this->warn('⚠️ No hay token configurado. El webhook podría fallar.');
        }

        $this->info("🧪 Probando webhook...");
        $this->info("📡 URL: {$webhookUrl}");
        $this->info("🎯 Evento: {$evento}");
        $this->newLine();

        $payload = [
            'evento' => $evento,
            'timestamp' => now()->toIso8601String(),
            'mensaje' => 'Prueba de conexión desde comando de Artisan',
            'datos_prueba' => [
                'producto_id' => 1,
                'nombre' => 'Producto de Prueba',
                'stock' => 100,
                'test' => true,
            ],
        ];

        try {
            $this->info('⏳ Enviando webhook...');
            
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $webhookToken,
                    'Content-Type' => 'application/json',
                    'X-Webhook-Test' => 'true',
                    'X-Webhook-Source' => 'Artisan Command',
                ])
                ->post($webhookUrl, $payload);

            $tiempo = round((microtime(true) - $startTime) * 1000, 2);

            $this->newLine();

            if ($response->successful()) {
                $this->info("✅ Webhook exitoso!");
                $this->info("📊 Status: {$response->status()}");
                $this->info("⏱️ Tiempo: {$tiempo}ms");
                
                if ($response->json()) {
                    $this->newLine();
                    $this->info("📦 Respuesta:");
                    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
                }

                return Command::SUCCESS;
            } else {
                $this->error("⚠️ Webhook respondió con error");
                $this->error("📊 Status: {$response->status()}");
                $this->error("⏱️ Tiempo: {$tiempo}ms");
                $this->newLine();
                $this->error("📦 Respuesta:");
                $this->line($response->body());

                return Command::FAILURE;
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error('❌ Error de conexión: No se pudo conectar al webhook');
            $this->error("💬 Mensaje: {$e->getMessage()}");
            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar webhook');
            $this->error("💬 Mensaje: {$e->getMessage()}");
            $this->newLine();
            
            if ($this->option('verbose')) {
                $this->error("🔍 Trace:");
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
