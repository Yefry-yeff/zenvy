<?php
/**
 * EJEMPLO DE RECEPTOR DE WEBHOOK DE SINCRONIZACIÓN DE INVENTARIO
 * 
 * CONFIGURACIÓN LOCAL - Laragon/XAMPP
 * 
 * Este archivo muestra cómo recibir y procesar los webhooks de sincronización
 * de inventario que envía Zenvy a tu página web.
 * 
 * Localización: En tu página web, en un controlador o ruta.
 * Endpoint: POST http://localhost:8000/api/webhook/inventory
 * 
 * CONFIGURAR EN .env:
 * WEBHOOK_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryWebhookController extends Controller
{
    /**
     * Recibir webhook de sincronización de inventario desde Zenvy
     * 
     * POST http://localhost:8000/api/webhook/inventory
     * 
     * Con este token (nunca vence):
     * Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
     */
    public function handle(Request $request)
    {
        try {
            // 1️⃣ VALIDAR TOKEN DE SEGURIDAD
            $tokenRecibido = str_replace('Bearer ', '', $request->header('Authorization') ?? '');
            $tokenEsperado = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb';
            
            if ($tokenRecibido !== $tokenEsperado) {
                Log::warning('Intento de webhook con token inválido', [
                    'ip' => $request->ip(),
                    'evento' => $request->input('evento'),
                    'token_recibido' => substr($tokenRecibido, 0, 20) . '...'
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // 2️⃣ OBTENER DATOS DEL WEBHOOK
            $evento = $request->input('evento');
            $timestamp = $request->input('timestamp');
            $payload = $request->all();
            
            Log::info('Webhook recibido de Zenvy', [
                'evento' => $evento,
                'timestamp' => $timestamp,
                'ip' => $request->ip()
            ]);
            
            // 3️⃣ PROCESAR POR TIPO DE EVENTO
            switch ($evento) {
                case 'inventario.compra_recibida':
                    $this->procesarCompraRecibida($payload);
                    break;
                    
                case 'inventario.venta_realizada':
                    $this->procesarVenta($payload);
                    break;
                    
                case 'inventario.stock_actualizado':
                    $this->procesarCambioStock($payload);
                    break;
                    
                case 'inventario.factura_anulada':
                    $this->procesarAnulacionFactura($payload);
                    break;
                    
                case 'inventario.sincronizacion_completa':
                    $this->procesarSincronizacionCompleta($payload);
                    break;
                    
                default:
                    Log::warning('Evento desconocido', ['evento' => $evento]);
            }
            
            // 4️⃣ RESPONDER EXITOSAMENTE
            return response()->json([
                'success' => true,
                'message' => 'Webhook procesado correctamente',
                'evento' => $evento
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de inventario', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 1. Procesar Compra Recibida
     * Cuando se ingresa stock en Zenvy
     */
    private function procesarCompraRecibida(array $payload)
    {
        $producto = $payload['producto'] ?? [];
        $detalles = $payload['detalles'] ?? [];
        
        Log::info('Procesando compra recibida', [
            'producto_id' => $producto['id'],
            'producto_nombre' => $producto['nombre'],
            'cantidad' => $producto['cantidad_ingresada']
        ]);
        
        // AQUÍ: Actualizar tu tabla de productos/inventario
        // Ejemplo:
        // DB::table('productos')->where('zenvy_id', $producto['id'])
        //    ->increment('stock', $producto['cantidad_ingresada']);
        
        // AQUÍ: Enviar notificación si stock bajo, notificar al cliente, etc.
        // $this->notificarCompraRecibida($producto);
    }
    
    /**
     * 2. Procesar Venta Realizada
     * Cuando se factura un producto en Zenvy (descuento de stock)
     */
    private function procesarVenta(array $payload)
    {
        $factura = $payload['factura'] ?? [];
        $productosVendidos = $payload['productos_vendidos'] ?? [];
        
        Log::info('Procesando venta realizada', [
            'factura_id' => $factura['id'],
            'total' => $factura['total'],
            'cantidad_items' => $factura['cantidad_items']
        ]);
        
        // AQUÍ: Descontar stock de tu tabla de productos
        // Ejemplo:
        foreach ($productosVendidos as $item) {
            // DB::table('productos')->where('zenvy_id', $item['id'])
            //    ->decrement('stock', $item['cantidad']);
            
            Log::info('Descuento de stock', [
                'producto_id' => $item['id'],
                'cantidad_vendida' => $item['cantidad']
            ]);
        }
        
        // AQUÍ: Actualizar estado de carrito si estaba pendiente
        // $this->actualizarCarritoSegunStock($productosVendidos);
    }
    
    /**
     * 3. Procesar Cambio de Stock
     * Cuando se modifica cantidad_disponible en recibido_bodega
     */
    private function procesarCambioStock(array $payload)
    {
        $producto = $payload['producto'] ?? [];
        $detalles = $payload['detalles'] ?? [];
        
        Log::info('Procesando cambio de stock', [
            'producto_id' => $producto['id'],
            'stock_anterior' => $producto['stock_anterior'],
            'stock_actual' => $producto['stock_actual'],
            'cambio' => $producto['cambio'],
            'razon' => $producto['razon']
        ]);
        
        // AQUÍ: Actualizar stock en tu tabla
        // Ejemplo:
        // DB::table('productos')->where('zenvy_id', $producto['id'])
        //    ->update(['stock' => $producto['stock_actual']]);
        
        // AQUÍ: Si es por venta, actualizar en tiempo real el carrito del cliente
        if ($producto['razon'] === 'factura') {
            Log::info('Venta registrada - Actualizar carrito', [
                'producto_id' => $producto['id']
            ]);
        }
    }
    
    /**
     * 4. Procesar Anulación de Factura
     * Cuando se anula una factura en Zenvy (restaura stock)
     */
    private function procesarAnulacionFactura(array $payload)
    {
        $factura = $payload['factura'] ?? [];
        $productosRestaurados = $payload['productos_restaurados'] ?? [];
        
        Log::info('Procesando anulación de factura', [
            'factura_id' => $factura['id'],
            'cantidad_items_restaurados' => $factura['cantidad_items_restaurados']
        ]);
        
        // AQUÍ: Restaurar stock en tu tabla
        // Ejemplo:
        foreach ($productosRestaurados as $item) {
            // DB::table('productos')->where('zenvy_id', $item['id'])
            //    ->increment('stock', $item['cantidad']);
            
            Log::info('Stock restaurado por anulación', [
                'producto_id' => $item['id'],
                'cantidad' => $item['cantidad']
            ]);
        }
    }
    
    /**
     * 5. Procesar Sincronización Completa
     * Cuando se fuerza sincronización completa
     */
    private function procesarSincronizacionCompleta(array $payload)
    {
        $totalProductos = $payload['total_productos'] ?? 0;
        $categorias = $payload['categorias'] ?? [];
        
        Log::info('Sincronización completa de inventario', [
            'total_productos' => $totalProductos,
            'total_categorias' => count($categorias)
        ]);
        
        // AQUÍ: Reemplazar todo el inventario
        // Esto es más pesado, pero asegura que todo esté sincronizado
        
        // Ejemplo:
        // DB::table('productos')->truncate(); // O marcar como no sincronizados
        
        foreach ($categorias as $categoria) {
            foreach ($categoria['productos'] as $producto) {
                // DB::table('productos')->updateOrCreate(
                //     ['zenvy_id' => $producto['id']],
                //     [
                //         'codigo_barra' => $producto['codigo_barra'],
                //         'nombre' => $producto['nombre'],
                //         'stock' => $producto['stock'],
                //         'precio' => $producto['precio_venta'],
                //         'categoria_id' => $categoria['categoria_id'],
                //         'categoria_nombre' => $categoria['categoria_nombre'],
                //         'last_sync' => now()
                //     ]
                // );
            }
        }
    }
    
    /**
     * HELPERS / FUNCIONES DE SOPORTE
     */
    
    /**
     * Notificar al administrador sobre compra recibida
     */
    private function notificarCompraRecibida(array $producto)
    {
        // Ejemplo: Enviar email, Slack, push notification
        Log::info('📦 Nueva compra recibida: ' . $producto['nombre']);
    }
    
    /**
     * Actualizar carrito pendiente según stock disponible
     */
    private function actualizarCarritoSegunStock(array $productosVendidos)
    {
        // Si hay un producto que se vendió y estaba en carrito pendiente,
        // verificar si hay stock para completar el carrito
        
        foreach ($productosVendidos as $item) {
            // Buscar carritos pendientes con este producto
            // Si stock < cantidad requerida, notificar al cliente
        }
    }
}

/**
 * CONFIGURAR ESTA RUTA EN TU ARCHIVO routes/api.php
 */

// Route::post('/webhook/inventory', [InventoryWebhookController::class, 'handle'])
//     ->name('webhook.inventory')
//     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/**
 * VARIABLES DE ENTORNO EN .env (TU PÁGINA WEB)
 */

// ============================================
// SINCRONIZACIÓN CON ZENVY
// ============================================
// Token que NUNCA vence - Para desarrollo local
// En producción, considera usar tokens con expiración
// WEBHOOK_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb

/**
 * CONFIGURACIÓN EN ZENVY (.env)
 * 
 * WEBHOOK_URL=http://localhost:8000/api/webhook/inventory
 * WEBHOOK_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
 */
