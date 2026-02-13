<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;

class WebhookController extends Controller
{
    /**
     * Recibir webhook de pedido desde ecommerce
     */
    public function receiveOrder(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Validar token
            if (!$this->validarToken($request)) {
                return $this->respuestaError('Token inválido o faltante', 401);
            }

            // Validar payload
            $validated = $request->validate([
                'pedido_id' => 'required|string',
                'cliente' => 'required|array',
                'items' => 'required|array',
                'items.*.producto_id' => 'required',
                'items.*.cantidad' => 'required|numeric|min:1',
                'total' => 'required|numeric',
            ]);

            // Procesar pedido
            // TODO: Implementar lógica de procesamiento de pedido
            
            // Registrar log
            $this->registrarLog(
                'webhook.order.received',
                $request,
                200,
                ['mensaje' => 'Pedido recibido correctamente'],
                true,
                microtime(true) - $startTime
            );

            Log::info('📥 Webhook pedido recibido', [
                'pedido_id' => $validated['pedido_id'],
                'items' => count($validated['items']),
                'total' => $validated['total']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido recibido y procesado',
                'pedido_id' => $validated['pedido_id'],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->registrarLog(
                'webhook.order.validation_error',
                $request,
                422,
                ['mensaje' => 'Error de validación', 'errores' => $e->errors()],
                false,
                microtime(true) - $startTime,
                $e->getMessage()
            );
            
            return $this->respuestaError('Error de validación', 422, $e->errors());
            
        } catch (\Exception $e) {
            $this->registrarLog(
                'webhook.order.error',
                $request,
                500,
                null,
                false,
                microtime(true) - $startTime,
                $e->getMessage()
            );
            
            Log::error('❌ Error procesando webhook de pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->respuestaError('Error interno al procesar pedido', 500);
        }
    }

    /**
     * Recibir webhook de sincronización de producto
     */
    public function syncProduct(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Validar token
            if (!$this->validarToken($request)) {
                return $this->respuestaError('Token inválido o faltante', 401);
            }

            // Validar payload
            $validated = $request->validate([
                'action' => 'required|in:create,update,delete',
                'producto' => 'required|array',
                'producto.id' => 'required',
                'producto.nombre' => 'required_if:action,create,update',
                'producto.precio' => 'required_if:action,create,update|numeric',
            ]);

            // Procesar sincronización
            // TODO: Implementar lógica de sincronización de producto
            
            $this->registrarLog(
                'webhook.product.sync',
                $request,
                200,
                ['mensaje' => 'Producto sincronizado correctamente'],
                true,
                microtime(true) - $startTime
            );

            Log::info('🔄 Webhook sincronización producto', [
                'action' => $validated['action'],
                'producto_id' => $validated['producto']['id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Producto sincronizado',
                'action' => $validated['action'],
            ], 200);

        } catch (\Exception $e) {
            $this->registrarLog(
                'webhook.product.error',
                $request,
                500,
                null,
                false,
                microtime(true) - $startTime,
                $e->getMessage()
            );
            
            return $this->respuestaError('Error al sincronizar producto', 500);
        }
    }

    /**
     * Webhook genérico
     */
    public function receiveGeneric(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Validar token
            if (!$this->validarToken($request)) {
                return $this->respuestaError('Token inválido o faltante', 401);
            }

            $payload = $request->all();
            
            $this->registrarLog(
                'webhook.generic',
                $request,
                200,
                ['mensaje' => 'Webhook genérico recibido'],
                true,
                microtime(true) - $startTime
            );

            Log::info('📨 Webhook genérico recibido', [
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook recibido',
                'received_at' => now()->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            $this->registrarLog(
                'webhook.generic.error',
                $request,
                500,
                null,
                false,
                microtime(true) - $startTime,
                $e->getMessage()
            );
            
            return $this->respuestaError('Error al procesar webhook', 500);
        }
    }

    /**
     * Validar token del webhook
     */
    private function validarToken(Request $request): bool
    {
        $token = $request->bearerToken();
        
        if (empty($token)) {
            return false;
        }

        // Validar contra el token configurado
        $expectedToken = config('app.webhook_token') ?? env('WEBHOOK_INVENTORY_TOKEN');
        
        return $token === $expectedToken;
    }

    /**
     * Respuesta de error estandarizada
     */
    private function respuestaError(string $mensaje, int $codigo = 400, $detalles = null)
    {
        $response = [
            'success' => false,
            'error' => [
                'message' => $mensaje,
                'code' => $codigo,
            ],
        ];

        if ($detalles) {
            $response['error']['details'] = $detalles;
        }

        return response()->json($response, $codigo);
    }

    /**
     * Registrar log de webhook
     */
    private function registrarLog(
        string $evento,
        Request $request,
        int $statusCode,
        $response,
        bool $exitoso,
        float $tiempoRespuesta,
        string $mensajeError = null
    ) {
        try {
            WebhookLog::create([
                'evento' => $evento,
                'direccion' => 'incoming',
                'url' => $request->fullUrl(),
                'payload' => json_encode($request->all()),
                'headers' => json_encode($request->headers->all()),
                'status_code' => $statusCode,
                'response' => json_encode($response),
                'exitoso' => $exitoso,
                'intentos' => 1,
                'tiempo_respuesta' => $tiempoRespuesta,
                'mensaje_error' => $mensajeError,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al registrar log de webhook', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
