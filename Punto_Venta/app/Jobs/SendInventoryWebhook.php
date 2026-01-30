<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendInventoryWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $payload,
        public string $cacheKey,
        public ?string $webhookUrl = null,
        public ?string $webhookToken = null,
        public int $timeout = 5
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $webhookUrl = $this->webhookUrl ?? config('app.webhook_url');
            $webhookToken = $this->webhookToken ?? config('app.webhook_token');
            
            if (!$webhookUrl || !$webhookToken) {
                Log::error('⚠️ JOB WEBHOOK - Configuración faltante', [
                    'webhook_url' => $webhookUrl ? 'OK' : 'FALTA',
                    'webhook_token' => $webhookToken ? 'OK' : 'FALTA'
                ]);
                return;
            }

            Log::info('🔄 JOB WEBHOOK - Procesando', [
                'evento' => $this->payload['evento'] ?? 'unknown',
                'cache_key' => $this->cacheKey,
                'url' => $webhookUrl
            ]);

            // Evitar duplicados con cache de corta duración
            if (Cache::has("webhook_sent_{$this->cacheKey}")) {
                Log::warning('⚠️ JOB WEBHOOK - Duplicado detectado y descartado', [
                    'cache_key' => $this->cacheKey
                ]);
                return;
            }

            // Preparar headers
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $webhookToken,
                'X-Event-Type' => $this->payload['evento'] ?? 'unknown',
                'X-Timestamp' => $this->payload['timestamp'] ?? now()->toIso8601String(),
            ];

            // Enviar request con timeout muy corto (fire-and-forget)
            // No esperar respuesta para evitar bloqueos
            try {
                $startTime = microtime(true);
                
                $response = Http::withHeaders($headers)
                    ->timeout(1) // 1 segundo de timeout
                    ->connectTimeout(1)
                    ->post($webhookUrl, $this->payload);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    Cache::put("webhook_sent_{$this->cacheKey}", true, now()->addSeconds(30));

                    Log::info('✅ JOB WEBHOOK - Enviado exitosamente', [
                        'evento' => $this->payload['evento'] ?? 'unknown',
                        'status' => $response->status(),
                        'duration_ms' => $duration,
                        'cache_key' => $this->cacheKey,
                        'url' => $webhookUrl
                    ]);
                } else {
                    Log::warning('⚠️ JOB WEBHOOK - Respuesta no exitosa', [
                        'evento' => $this->payload['evento'] ?? 'unknown',
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'duration_ms' => $duration
                    ]);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Timeout o error de conexión - ignorar, es fire-and-forget
                Log::info('🚀 JOB WEBHOOK - Fire-and-forget (timeout/no respuesta)', [
                    'evento' => $this->payload['evento'] ?? 'unknown',
                    'cache_key' => $this->cacheKey,
                    'nota' => 'Esto es normal en modo fire-and-forget'
                ]);
                
                // Marcar como enviado de todas formas
                Cache::put("webhook_sent_{$this->cacheKey}", true, now()->addSeconds(30));
            }

        } catch (\Exception $e) {
            Log::error('❌ JOB WEBHOOK - Error inesperado', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cache_key' => $this->cacheKey,
            ]);
        }
    }
}
