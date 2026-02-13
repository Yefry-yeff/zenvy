<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;

class EnviarWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [1, 5, 15]; // segundos entre reintentos
    public $timeout = 30;

    protected $evento;
    protected $payload;
    protected $relacionadoId;
    protected $relacionadoTipo;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $evento,
        array $payload,
        int $relacionadoId = null,
        string $relacionadoTipo = null
    ) {
        $this->evento = $evento;
        $this->payload = $payload;
        $this->relacionadoId = $relacionadoId;
        $this->relacionadoTipo = $relacionadoTipo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('app.webhook_url');
        $webhookToken = config('app.webhook_token');
        $timeout = config('app.webhook_timeout', 5);

        if (empty($webhookUrl) || empty($webhookToken)) {
            Log::warning('⚠️ Webhook no configurado - Job cancelado', [
                'evento' => $this->evento,
            ]);
            return;
        }

        $startTime = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $webhookToken,
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $this->evento,
                    'X-Webhook-Source' => 'Zenvy POS',
                ])
                ->post($webhookUrl, $this->payload);

            $tiempoRespuesta = microtime(true) - $startTime;

            // Registrar log exitoso
            $this->registrarLog(
                $response->status(),
                $response->body(),
                true,
                $tiempoRespuesta,
                $webhookUrl
            );

            Log::info('✅ Webhook enviado exitosamente', [
                'evento' => $this->evento,
                'status' => $response->status(),
                'tiempo' => round($tiempoRespuesta * 1000, 2) . 'ms',
                'intento' => $this->attempts(),
            ]);

        } catch (\Exception $e) {
            $tiempoRespuesta = microtime(true) - $startTime;

            // Registrar log de error
            $this->registrarLog(
                null,
                null,
                false,
                $tiempoRespuesta,
                $webhookUrl,
                $e->getMessage()
            );

            Log::error('❌ Error al enviar webhook', [
                'evento' => $this->evento,
                'error' => $e->getMessage(),
                'intento' => $this->attempts(),
                'max_intentos' => $this->tries,
            ]);

            // Re-lanzar excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('💥 Webhook falló después de todos los intentos', [
            'evento' => $this->evento,
            'error' => $exception->getMessage(),
            'intentos' => $this->tries,
        ]);
    }

    /**
     * Registrar log en base de datos
     */
    private function registrarLog(
        ?int $statusCode,
        ?string $response,
        bool $exitoso,
        float $tiempoRespuesta,
        string $url,
        ?string $mensajeError = null
    ): void {
        try {
            WebhookLog::create([
                'evento' => $this->evento,
                'direccion' => 'outgoing',
                'url' => $url,
                'payload' => json_encode($this->payload),
                'status_code' => $statusCode,
                'response' => $response,
                'exitoso' => $exitoso,
                'intentos' => $this->attempts(),
                'tiempo_respuesta' => $tiempoRespuesta,
                'mensaje_error' => $mensajeError,
                'relacionado_id' => $this->relacionadoId,
                'relacionado_tipo' => $this->relacionadoTipo,
            ]);

            // Actualizar estadísticas en caché
            $this->actualizarEstadisticas($exitoso, $tiempoRespuesta);

        } catch (\Exception $e) {
            Log::error('Error al registrar log de webhook en BD', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar estadísticas en caché
     */
    private function actualizarEstadisticas(bool $exitoso, float $tiempoRespuesta): void
    {
        try {
            $total = cache()->increment('webhook_stats_total', 1);
            
            if ($exitoso) {
                cache()->increment('webhook_stats_exitosos', 1);
            } else {
                cache()->increment('webhook_stats_fallidos', 1);
            }

            cache()->increment('webhook_stats_ultima_hora', 1);
            
            // Calcular promedio de tiempo (simple)
            $promedioActual = cache()->get('webhook_stats_promedio_tiempo', 0);
            $nuevoPromedio = ($promedioActual * ($total - 1) + $tiempoRespuesta) / $total;
            cache()->put('webhook_stats_promedio_tiempo', $nuevoPromedio, now()->addDay());

            // Guardar último envío
            cache()->put('webhook_ultimo_envio', [
                'fecha' => now()->format('Y-m-d H:i:s'),
                'evento' => $this->evento,
                'estado' => $exitoso ? 'exitoso' : 'fallido',
            ], now()->addDay());

            // Resetear contador de última hora cada hora
            cache()->put('webhook_stats_reset_hora', now()->addHour(), now()->addHour());

        } catch (\Exception $e) {
            Log::error('Error al actualizar estadísticas de webhook', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
