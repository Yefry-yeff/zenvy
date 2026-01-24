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
                Log::warning('Webhook no configurado correctamente');
                return;
            }

            // Evitar duplicados con cache de corta duración
            if (Cache::has("webhook_sent_{$this->cacheKey}")) {
                Log::debug('Webhook duplicado descartado', ['cache_key' => $this->cacheKey]);
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
                $response = Http::withHeaders($headers)
                    ->timeout(1) // 1 segundo de timeout
                    ->connectTimeout(1)
                    ->post($webhookUrl, $this->payload);

                if ($response->successful()) {
                    Cache::put("webhook_sent_{$this->cacheKey}", true, now()->addSeconds(30));

                    Log::info('Webhook de inventario enviado', [
                        'evento' => $this->payload['evento'] ?? 'unknown',
                        'status' => $response->status(),
                        'cache_key' => $this->cacheKey,
                    ]);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Timeout o error de conexión - ignorar, es fire-and-forget
                Log::debug('Webhook no esperó respuesta (fire-and-forget)', [
                    'cache_key' => $this->cacheKey,
                ]);
                
                // Marcar como enviado de todas formas
                Cache::put("webhook_sent_{$this->cacheKey}", true, now()->addSeconds(30));
            }

        } catch (\Exception $e) {
            Log::debug('Error en job de webhook (ignorado)', [
                'exception' => $e->getMessage(),
                'cache_key' => $this->cacheKey,
            ]);
        }
    }
}
