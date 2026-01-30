<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de Sincronización de Inventario con Página Web
 * 
 * Se encarga de sincronizar cambios de stock con la página web en tiempo real
 * cuando hay:
 * - Ingreso de compras
 * - Facturación de productos
 * - Ajustes de inventario
 */
class WebInventorySyncService
{
    private $webhookUrl;
    private $webhookToken;
    private $timeout = 5;
    
    public function __construct()
    {
        $this->webhookUrl = config('app.webhook_url');
        $this->webhookToken = config('app.webhook_token');
    }
    
    /**
     * Sincronizar actualización de stock de producto
     * Se ejecuta cuando hay cambios en cantidad_disponible de recibido_bodega
     * 
     * @param int $productoId
     * @param string $nombreProducto
     * @param int $stockAnterior
     * @param int $stockActual
     * @param string $razon (compra, factura, ajuste, anulacion)
     * @param array $detalles información adicional
     */
    public function sincronizarCambioStock(
        int $productoId,
        string $nombreProducto,
        int $stockAnterior,
        int $stockActual,
        string $razon = 'ajuste',
        array $detalles = []
    ): bool {
        if (!$this->estaConfigurado()) {
            Log::warning('⚠️ Webhook NO configurado - WEBHOOK_URL o WEBHOOK_TOKEN faltante en .env', [
                'producto_id' => $productoId,
                'producto' => $nombreProducto,
                'razon' => $razon,
                'stock_actual' => $stockActual
            ]);
            return false;
        }
        
        $cambio = $stockActual - $stockAnterior;
        
        $payload = [
            'evento' => 'inventario.stock_actualizado',
            'timestamp' => now()->toIso8601String(),
            'producto' => [
                'id' => $productoId,
                'nombre' => $nombreProducto,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $stockActual,
                'cambio' => $cambio,
                'razon' => $razon,
            ],
            'detalles' => $detalles,
        ];
        
        Log::info('📦 WEBHOOK INVENTARIO - Preparando envío', [
            'evento' => 'stock_actualizado',
            'producto_id' => $productoId,
            'producto' => $nombreProducto,
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $stockActual,
            'cambio' => ($cambio >= 0 ? '+' : '') . $cambio,
            'razon' => $razon,
            'webhook_url' => $this->webhookUrl
        ]);
        
        return $this->enviarWebhook($payload, "stock_producto_{$productoId}_{$razon}");
    }
    
    /**
     * Sincronizar ingreso de compra
     * Se ejecuta cuando se recibe un producto en bodega
     * 
     * @param int $productoId
     * @param string $nombreProducto
     * @param int $cantidadRecibida
     * @param array $detalles (numero_compra, proveedor, etc)
     */
    public function sincronizarCompraRecibida(
        int $productoId,
        string $nombreProducto,
        int $cantidadRecibida,
        array $detalles = []
    ): bool {
        if (!$this->estaConfigurado()) {
            Log::warning('⚠️ Webhook NO configurado - Compra no sincronizada', [
                'producto_id' => $productoId,
                'cantidad' => $cantidadRecibida
            ]);
            return false;
        }
        
        $payload = [
            'evento' => 'inventario.compra_recibida',
            'timestamp' => now()->toIso8601String(),
            'producto' => [
                'id' => $productoId,
                'nombre' => $nombreProducto,
                'cantidad_ingresada' => $cantidadRecibida,
            ],
            'detalles' => $detalles,
        ];
        
        Log::info('📥 WEBHOOK INVENTARIO - Compra recibida', [
            'evento' => 'compra_recibida',
            'producto_id' => $productoId,
            'producto' => $nombreProducto,
            'cantidad' => $cantidadRecibida,
            'detalles' => $detalles,
            'webhook_url' => $this->webhookUrl
        ]);
        
        return $this->enviarWebhook($payload, "compra_producto_{$productoId}");
    }
    
    /**
     * Sincronizar factura/venta
     * Se ejecuta cuando se factura un producto (descuento de stock)
     * 
     * @param int $facturaid
     * @param array $items Array de productos vendidos
     * @param float $total
     * @param array $detalles
     */
    public function sincronizarVenta(
        int $facturaId,
        array $items,
        float $total,
        array $detalles = []
    ): bool {
        if (!$this->estaConfigurado()) {
            Log::warning('⚠️ Webhook NO configurado - Venta no sincronizada', [
                'factura_id' => $facturaId,
                'items' => count($items),
                'total' => $total
            ]);
            return false;
        }
        
        $payload = [
            'evento' => 'inventario.venta_realizada',
            'timestamp' => now()->toIso8601String(),
            'factura' => [
                'id' => $facturaId,
                'total' => $total,
                'cantidad_items' => count($items),
            ],
            'productos_vendidos' => $items,
            'detalles' => $detalles,
        ];
        
        Log::info('💰 WEBHOOK INVENTARIO - Venta realizada', [
            'evento' => 'venta_realizada',
            'factura_id' => $facturaId,
            'cantidad_items' => count($items),
            'total' => number_format($total, 2),
            'productos' => collect($items)->pluck('nombre')->take(3)->join(', '),
            'webhook_url' => $this->webhookUrl
        ]);
        
        return $this->enviarWebhook($payload, "venta_factura_{$facturaId}");
    }
    
    /**
     * Sincronizar anulación de factura (restauración de stock)
     * 
     * @param int $facturaId
     * @param array $items productos que se restauran
     * @param array $detalles
     */
    public function sincronizarAnulacionFactura(
        int $facturaId,
        array $items,
        array $detalles = []
    ): bool {
        if (!$this->estaConfigurado()) {
            Log::warning('⚠️ Webhook NO configurado - Anulación no sincronizada', [
                'factura_id' => $facturaId,
                'items' => count($items)
            ]);
            return false;
        }
        
        $payload = [
            'evento' => 'inventario.factura_anulada',
            'timestamp' => now()->toIso8601String(),
            'factura' => [
                'id' => $facturaId,
                'cantidad_items_restaurados' => count($items),
            ],
            'productos_restaurados' => $items,
            'detalles' => $detalles,
        ];
        
        Log::info('❌ WEBHOOK INVENTARIO - Factura anulada', [
            'evento' => 'factura_anulada',
            'factura_id' => $facturaId,
            'items_restaurados' => count($items),
            'productos' => collect($items)->pluck('nombre')->take(3)->join(', '),
            'webhook_url' => $this->webhookUrl
        ]);
        
        return $this->enviarWebhook($payload, "anulacion_factura_{$facturaId}");
    }
    
    /**
     * Sincronizar inventario completo (para sincronización inicial o forzada)
     * 
     * @param array $inventario Array de todos los productos con stock agrupados por categoría
     * @return bool
     */
    public function sincronizarInventarioCompleto(array $inventario): bool
    {
        if (!$this->estaConfigurado()) {
            Log::warning('⚠️ Webhook NO configurado - Sincronización completa no realizada');
            return false;
        }
        
        $totalProductos = 0;
        $totalStock = 0;
        
        foreach ($inventario as $categoria) {
            $totalProductos += $categoria['total_productos'];
            $totalStock += $categoria['stock_total'];
        }
        
        $payload = [
            'evento' => 'inventario.sincronizacion_completa',
            'timestamp' => now()->toIso8601String(),
            'total_categorias' => count($inventario),
            'total_productos' => $totalProductos,
            'total_stock' => $totalStock,
            'categorias' => $inventario,
        ];
        
        Log::info('🔄 WEBHOOK INVENTARIO - Sincronización completa', [
            'evento' => 'sincronizacion_completa',
            'total_categorias' => count($inventario),
            'total_productos' => $totalProductos,
            'total_stock' => $totalStock,
            'webhook_url' => $this->webhookUrl
        ]);
        
        return $this->enviarWebhook($payload, "sync_completa_" . now()->timestamp);
    }
    
    /**
     * Enviar webhook a la página web (de forma asincrónica)
     */
    private function enviarWebhook(array $payload, string $cacheKey): bool
    {
        try {
            // Si la cola está en sync, ejecutar el envío en un proceso separado (fire-and-forget)
            if (config('queue.default') === 'sync') {
                Log::info('🚀 Enviando webhook en modo SYNC (fire-and-forget)', [
                    'evento' => $payload['evento'],
                    'metodo' => 'socket directo',
                    'cache_key' => $cacheKey
                ]);
                // Usar un subproceso sin bloquear el request principal
                $this->enviarWebhookFireAndForget($payload, $cacheKey);
                return true;
            }
            
            // Si hay una cola configurada, usar Job
            \App\Jobs\SendInventoryWebhook::dispatch(
                $payload,
                $cacheKey,
                $this->webhookUrl,
                $this->webhookToken,
                $this->timeout
            );
            
            Log::info('✅ Job de webhook despachado a la cola', [
                'evento' => $payload['evento'],
                'cache_key' => $cacheKey,
                'queue' => config('queue.default')
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error al despachar webhook', [
                'exception' => $e->getMessage(),
                'cache_key' => $cacheKey,
            ]);
            
            return false;
        }
    }
    
    /**
     * Enviar webhook sin bloquear (Fire-and-Forget) usando socket directo
     * No espera respuesta del servidor
     */
    private function enviarWebhookFireAndForget(array $payload, string $cacheKey): void
    {
        try {
            // Evitar duplicados
            if (Cache::has("webhook_sent_{$cacheKey}")) {
                Log::debug('Webhook duplicado descartado', ['cache_key' => $cacheKey]);
                return;
            }
            
            Cache::put("webhook_sent_{$cacheKey}", true, now()->addSeconds(30));
            
            // Parsear URL
            $url = parse_url($this->webhookUrl);
            $host = $url['host'];
            $port = $url['port'] ?? 80;
            $path = ($url['path'] ?? '/') . (isset($url['query']) ? '?' . $url['query'] : '');
            
            // Preparar payload JSON
            $jsonPayload = json_encode($payload);
            
            // Preparar headers
            $headers = [
                'POST ' . $path . ' HTTP/1.1',
                'Host: ' . $host,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonPayload),
                'Authorization: Bearer ' . $this->webhookToken,
                'X-Event-Type: ' . ($payload['evento'] ?? 'unknown'),
                'Connection: Close'
            ];
            
            // Enviar via socket (no bloqueante)
            if (@$socket = fsockopen($host, $port, $errno, $errstr, 1)) {
                fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $jsonPayload);
                fclose($socket);
                
                Log::info('✅ Webhook enviado via socket (fire-and-forget)', [
                    'evento' => $payload['evento'] ?? 'unknown',
                    'host' => $host,
                    'port' => $port,
                    'cache_key' => $cacheKey
                ]);
            } else {
                Log::error('❌ No se pudo abrir socket para webhook', [
                    'host' => $host,
                    'port' => $port,
                    'errno' => $errno,
                    'error' => $errstr
                ]);
            }
            
        } catch (\Exception $e) {
            Log::debug('Fire-and-forget webhook (ignorado)', ['cache_key' => $cacheKey]);
        }
    }
    
    /**
     * Verificar si webhook está configurado
     */
    public function estaConfigurado(): bool
    {
        return !empty($this->webhookUrl) && !empty($this->webhookToken);
    }
    
    /**
     * Obtener URL configurada
     */
    public function obtenerWebhookUrl(): ?string
    {
        return $this->webhookUrl ?: null;
    }
}
