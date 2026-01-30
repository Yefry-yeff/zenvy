<?php

namespace App\Services\Api;

use App\Repositories\ProductRepository;
use App\Models\Factura;
use App\Models\FacturaHasProducto;
use App\Models\RecibidoBodega;
use App\Models\PedidoWeb;
use App\Models\PedidoWebItem;
use App\Exceptions\Api\InsufficientStockException;
use App\Exceptions\Api\ProductNotFoundException;
use App\Services\WebInventorySyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SalesService
{
    public function __construct(
        private ProductRepository $productRepo,
        private \App\Services\ReservaInventarioService $reservaService,
        private WebInventorySyncService $syncService
    ) {}
    
    /**
     * Crear pedido web (preview antes de facturar)
     * Este método NO descuenta stock ni genera factura
     */
    public function createPreviewOrder(array $data, $apiClient): PedidoWeb
    {
        return DB::transaction(function() use ($data, $apiClient) {
            // 1. Validar que los productos existan y haya stock disponible
            $this->validateStock($data['items']);
            
            // 2. Generar número de pedido
            $numeroPedido = $this->generarNumeroPedido();
            
            // 3. Preparar dirección completa
            $direccionCompleta = null;
            if ($data['delivery_type'] === 'domicilio' && !empty($data['delivery_address'])) {
                $direccionCompleta = $data['delivery_address'];
            }
            
            // 4. Preparar metadata
            $metadata = [
                'delivery_type' => $data['delivery_type'],
                'api_client' => $apiClient->name ?? 'unknown',
                'api_client_id' => $apiClient->id ?? null,
                'external_order_id' => $data['external_order_id'] ?? null,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
            ];
            
            // Agregar información de transferencia bancaria si existe
            if (isset($data['transfer_info'])) {
                $metadata['transfer_info'] = $data['transfer_info'];
            }
            
            // 5. Crear pedido web
            $pedido = PedidoWeb::create([
                'numero_pedido' => $numeroPedido,
                'estado' => 'pendiente',
                'cliente_nombre' => $data['customer_name'],
                'cliente_email' => $data['customer_email'] ?? null,
                'cliente_telefono' => $data['customer_phone'] ?? null,
                'cliente_rtn' => $data['customer_rtn'] ?? null,
                'cliente_direccion' => $direccionCompleta,
                'subtotal' => $data['subtotal'],
                'descuento' => $data['discount'] ?? 0,
                'isv' => $data['tax'],
                'total' => $data['total'],
                'metodo_pago' => $data['payment_method'] ?? null,
                'notas' => $data['notes'] ?? null,
                'metadata' => $metadata,
                'leido' => false,
            ]);
            
            // 6. Crear items del pedido
            foreach ($data['items'] as $item) {
                $product = DB::table('producto')->where('id', $item['product_id'])->first();
                
                if (!$product) {
                    throw new ProductNotFoundException("Producto ID {$item['product_id']} no encontrado");
                }
                
                $cantidad = $item['quantity'];
                $precioUnidad = $item['price'];
                $descuentoPorcentaje = $item['discount'] ?? 0;
                
                $subtotalItemSinDescuento = $cantidad * $precioUnidad;
                $descuentoMonto = ($subtotalItemSinDescuento * $descuentoPorcentaje) / 100;
                $subtotalItem = $subtotalItemSinDescuento - $descuentoMonto;
                $isvItem = $subtotalItem * 0.15;
                $totalItem = $subtotalItem + $isvItem;
                
                PedidoWebItem::create([
                    'pedido_web_id' => $pedido->id,
                    'producto_id' => $product->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnidad,
                    'subtotal' => $subtotalItem,
                    'isv' => $isvItem,
                    'total' => $totalItem,
                ]);
            }
            
            Log::info('API: Pedido web creado (preview)', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'cliente' => $data['customer_name'],
                'total' => $pedido->total,
                'items' => count($data['items']),
            ]);
            
            // 7. Crear reservas de inventario
            $resultadoReservas = $this->reservaService->crearReservas($pedido);
            
            if (!$resultadoReservas['success']) {
                throw new InsufficientStockException(
                    'No se pudo reservar el inventario: ' . implode(', ', $resultadoReservas['errores'])
                );
            }
            
            Log::info('Reservas de inventario creadas para pedido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'reservas_creadas' => count($resultadoReservas['reservas']),
            ]);
            
            return $pedido->load('items');
        });
    }
    
    /**
     * Procesar/Facturar un pedido web existente
     * Este método SÍ descuenta stock y genera la factura
     */
    public function processOrder(int $pedidoId, int $userId = 1): Factura
    {
        // Lock para evitar procesamiento concurrente
        $lockKey = "process_order_{$pedidoId}";
        $lock = Cache::lock($lockKey, 10);
        
        try {
            if (!$lock->get()) {
                throw new \Exception('Este pedido ya está siendo procesado');
            }
            
            return DB::transaction(function() use ($pedidoId, $userId) {
                // 1. Obtener pedido web
                $pedido = PedidoWeb::with('items')->findOrFail($pedidoId);
                
                // 2. Verificar que esté pendiente
                if ($pedido->estado !== 'pendiente') {
                    throw new \Exception("El pedido {$pedido->numero_pedido} ya fue procesado (Estado: {$pedido->estado})");
                }
                
                // 3. Marcar como procesando
                $pedido->marcarComoProcesando($userId);
                
                // 4. Validar stock nuevamente (por si cambió desde la creación del pedido)
                $items = $pedido->items->map(function($item) {
                    return [
                        'product_id' => $item->producto_id,
                        'quantity' => $item->cantidad,
                    ];
                })->toArray();
                
                $this->validateStock($items);
                
                // 5. Crear transacción
                $transaccionId = DB::table('transaccion')->insertGetId([
                    'caja_id' => 1,
                ]);
                
                // 6. Crear comentario CORTO para factura (max 255 caracteres)
                $comentarioParts = [
                    'Pedido Web: ' . $pedido->numero_pedido,
                    'Cliente: ' . substr($pedido->cliente_nombre, 0, 50)
                ];
                
                // Agregar información de método de pago
                if ($pedido->metodo_pago) {
                    $comentarioParts[] = 'Pago: ' . $pedido->metodo_pago;
                }
                
                // Agregar información de tipo de entrega
                $metadata = $pedido->metadata;
                if (isset($metadata['delivery_type'])) {
                    $tipoEntrega = $metadata['delivery_type'] === 'domicilio' ? 'Envío a domicilio' : 'Retiro en tienda';
                    $comentarioParts[] = $tipoEntrega;
                }
                
                // Agregar costo de envío si existe
                if (isset($metadata['shipping_cost']) && $metadata['shipping_cost'] > 0) {
                    $comentarioParts[] = 'Envío: L.' . number_format($metadata['shipping_cost'], 2);
                }
                
                $comentario = implode(' | ', $comentarioParts);
                $comentario = substr($comentario, 0, 255); // Limitar a 255 caracteres
                
                // 7. Crear factura
                // Calcular subtotal sin incluir el costo de envío
                $shippingCost = $metadata['shipping_cost'] ?? 0;
                $subtotalProductos = $pedido->subtotal - $shippingCost;
                
                $factura = Factura::create([
                    'cai_id' => 1,
                    'transaccion_id' => $transaccionId,
                    'nombre_cliente' => $pedido->cliente_nombre,
                    'rtn' => $pedido->cliente_rtn ?? '',
                    'sub_total' => $pedido->subtotal,
                    'sub_total_grabado' => $pedido->subtotal,
                    'sub_total_exento' => 0,
                    'isv' => $pedido->isv,
                    'total' => $pedido->total,
                    'credito' => 0,
                    'dias_credito' => 0,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => now()->addDays(30),
                    'comentario' => $comentario,
                    'porc_descuento' => 0,
                    'monto_descuento' => $pedido->descuento,
                    'precio_dolar' => 1,
                    'estado_factura_id' => 1,
                    'tipo_facturacion_id' => 1,
                    'users_id' => $userId,
                ]);
                
                // 8. Crear items de factura y descontar stock
                $indice = 1;
                foreach ($pedido->items as $item) {
                    FacturaHasProducto::create([
                        'factura_id' => $factura->id,
                        'producto_id' => $item->producto_id,
                        'seccion_id' => 2,
                        'unidad_medida_id' => 1,
                        'precio_id' => 1,
                        'indice' => $indice++,
                        'numero_unidades_resta_inventario' => $item->cantidad,
                        'resta_inventario_total' => $item->cantidad,
                        'precio_unidad' => $item->precio_unitario,
                        'cantidad' => $item->cantidad,
                        'subtotal' => $item->subtotal,
                        'descuento' => 0,
                        'isv_aplicado' => 15.00,
                        'isv' => $item->isv,
                        'total' => $item->total,
                        'idPrecioSeleccionado' => '1',
                        'precio_seleccionado' => $item->precio_unitario,
                    ]);
                    
                    // Descontar stock
                    $this->decrementarStockBodega($item->producto_id, $item->cantidad);
                    
                    // Sincronizar cambio de stock con página web
                    $this->sincronizarInventarioWeb($item->producto_id, $item->cantidad);
                }
                
                // 9. Consumir reservas de inventario (marcarlas como consumidas)
                $this->reservaService->consumirReservas($pedido, $userId);
                
                // 10. Marcar pedido como facturado
                $pedido->marcarComoFacturado($factura->id);
                
                // 11. Invalidar cache
                Cache::flush();
                
                Log::info('API: Pedido web facturado exitosamente', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'factura_id' => $factura->id,
                    'usuario_id' => $userId,
                    'metodo_pago' => $pedido->metodo_pago,
                    'delivery_type' => $metadata['delivery_type'] ?? null,
                    'shipping_cost' => $shippingCost,
                    'tiene_transferencia' => isset($metadata['transfer_info']),
                ]);
                
                return $factura->load('productos');
            });
            
        } finally {
            optional($lock)->release();
        }
    }
    
    /**
     * Generar número de pedido único
     */
    private function generarNumeroPedido(): string
    {
        $year = date('Y');
        $lastPedido = PedidoWeb::where('numero_pedido', 'LIKE', "PW-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastPedido) {
            $lastNumber = (int) substr($lastPedido->numero_pedido, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return "PW-{$year}-{$newNumber}";
    }
    
    /**
     * Crear venta/factura directa con control de stock
     * DEPRECADO: Usar createPreviewOrder() + processOrder() para el flujo recomendado
     */
    public function createSale(array $data, $apiClient): Factura
    {
        // Lock para evitar ventas concurrentes
        $lockKey = "sale_creation_" . md5(json_encode($data));
        $lock = Cache::lock($lockKey, 10);
        
        try {
            if (!$lock->get()) {
                throw new \Exception('Esta venta ya está siendo procesada');
            }
            
            // Transacción para garantizar atomicidad
            return DB::transaction(function() use ($data, $apiClient) {
                // 1. Validar stock disponible
                $this->validateStock($data['items']);
                
                // 2. Crear transacción (registro de la operación)
                $transaccionId = DB::table('transaccion')->insertGetId([
                    'caja_id' => 1, // Caja predeterminada para API
                ]);
                
                // 3. Preparar comentario con información de entrega
                $comentario = 'Venta desde API - E-commerce';
                $comentario .= '\nCliente: ' . $data['customer_name'];
                $comentario .= '\nEmail: ' . $data['customer_email'];
                $comentario .= '\nTeléfono: ' . $data['customer_phone'];
                $comentario .= '\nMétodo de pago: ' . $data['payment_method'];
                $comentario .= '\nTipo de entrega: ' . ($data['delivery_type'] === 'retiro_tienda' ? 'Retiro en tienda' : 'Entrega a domicilio');
                if ($data['delivery_type'] === 'domicilio' && !empty($data['delivery_address'])) {
                    $comentario .= '\nDirección de envío: ' . $data['delivery_address'];
                }
                if (!empty($data['notes'])) {
                    $comentario .= '\nNotas: ' . $data['notes'];
                }
                
                // 4. Crear factura principal
                $factura = Factura::create([
                    'cai_id' => 1, // CAI por defecto - ajustar según configuración
                    'transaccion_id' => $transaccionId,
                    'nombre_cliente' => $data['customer_name'],
                    'rtn' => $data['customer_rtn'] ?? '',
                    'sub_total' => $data['subtotal'],
                    'sub_total_grabado' => $data['subtotal'],
                    'sub_total_exento' => 0,
                    'isv' => $data['tax'],
                    'total' => $data['total'],
                    'credito' => 0,
                    'dias_credito' => 0,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => now()->addDays(30),
                    'comentario' => $comentario,
                    'porc_descuento' => 0,
                    'monto_descuento' => $data['discount'] ?? 0,
                    'precio_dolar' => 1,
                    'estado_factura_id' => 1, // 1 = completada
                    'tipo_facturacion_id' => 1, // Ajustar según tu configuración
                    'users_id' => 1, // Usuario del sistema - ajustar si es necesario
                ]);
                
                // 5. Crear items de factura y descontar stock
                $indice = 1;
                foreach ($data['items'] as $item) {
                    // Obtener producto por ID
                    $product = DB::table('producto')->where('id', $item['product_id'])->first();
                    
                    if (!$product) {
                        throw new ProductNotFoundException("Producto ID {$item['product_id']} no encontrado");
                    }
                    
                    $cantidad = $item['quantity'];
                    $precioUnidad = $item['price'];
                    $descuentoPorcentaje = $item['discount'] ?? 0;
                    
                    // Calcular subtotal del item antes del descuento
                    $subtotalItemSinDescuento = $cantidad * $precioUnidad;
                    
                    // Calcular descuento en monto
                    $descuentoMonto = ($subtotalItemSinDescuento * $descuentoPorcentaje) / 100;
                    
                    // Subtotal después del descuento
                    $subtotalItem = $subtotalItemSinDescuento - $descuentoMonto;
                    
                    // Calcular ISV (15%)
                    $isvItem = $subtotalItem * 0.15;
                    
                    // Total del item
                    $totalItem = $subtotalItem + $isvItem;
                    
                    // Crear item de factura
                    FacturaHasProducto::create([
                        'factura_id' => $factura->id,
                        'producto_id' => $product->id,
                        'seccion_id' => 2, // Sección por defecto para API
                        'unidad_medida_id' => $product->unidad_medida_venta_id ?? 1,
                        'precio_id' => 1,
                        'indice' => $indice++,
                        'numero_unidades_resta_inventario' => $cantidad,
                        'resta_inventario_total' => $cantidad,
                        'precio_unidad' => $precioUnidad,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotalItem,
                        'descuento' => $descuentoMonto,
                        'isv_aplicado' => 15.00,
                        'isv' => $isvItem,
                        'total' => $totalItem,
                        'idPrecioSeleccionado' => '1',
                        'precio_seleccionado' => $precioUnidad,
                    ]);
                    
                    // Descontar stock de recibido_bodega
                    $this->decrementarStockBodega($product->id, $cantidad);
                }
                
                // 6. Invalidar cache de inventario
                Cache::flush(); // Driver 'file' no soporta tags
                
                // 7. Log de éxito
                Log::info('API: Venta creada exitosamente', [
                    'factura_id' => $factura->id,
                    'cliente' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'telefono' => $data['customer_phone'],
                    'tipo_entrega' => $data['delivery_type'],
                    'metodo_pago' => $data['payment_method'],
                    'total' => $factura->total,
                    'items' => count($data['items']),
                    'api_client' => $apiClient->name,
                ]);
                
                return $factura->load('productos');
            });
            
        } finally {
            optional($lock)->release();
        }
    }
    
    /**
     * Validar que hay stock disponible para todos los items
     */
    private function validateStock(array $items): void
    {
        $insufficientItems = [];
        
        foreach ($items as $item) {
            // Obtener producto por ID
            $product = DB::table('producto')->where('id', $item['product_id'])->first();
            
            if (!$product) {
                throw new ProductNotFoundException("Producto ID {$item['product_id']} no encontrado");
            }
            
            // Obtener stock disponible considerando reservas activas
            $stockDisponible = $this->reservaService->getStockDisponible($product->id);
            
            if ($stockDisponible < $item['quantity']) {
                $insufficientItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product->nombre_producto ?? $product->nombre,
                    'requested' => $item['quantity'],
                    'available' => $stockDisponible,
                ];
            }
        }
        
        if (!empty($insufficientItems)) {
            throw new InsufficientStockException($insufficientItems);
        }
    }
    
    /**
     * Decrementar stock de bodega usando FIFO
     */
    private function decrementarStockBodega(int $productoId, int $cantidad): void
    {
        $cantidadRestante = $cantidad;
        
        // Obtener lotes con stock disponible ordenados por FIFO (fecha más antigua primero)
        $lotes = RecibidoBodega::where('producto_id', $productoId)
            ->where('estado_id', 1)
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha_recibido', 'asc')
            ->lockForUpdate() // Bloquear para evitar race conditions
            ->get();
        
        foreach ($lotes as $lote) {
            if ($cantidadRestante <= 0) {
                break;
            }
            
            $cantidadADescontar = min($lote->cantidad_disponible, $cantidadRestante);
            
            $lote->cantidad_disponible -= $cantidadADescontar;
            $lote->save();
            
            $cantidadRestante -= $cantidadADescontar;
            
            Log::info('Stock descontado de bodega', [
                'lote_id' => $lote->id,
                'producto_id' => $productoId,
                'descontado' => $cantidadADescontar,
                'restante_lote' => $lote->cantidad_disponible,
            ]);
        }
        
        if ($cantidadRestante > 0) {
            throw new InsufficientStockException([
                [
                    'product_id' => $productoId,
                    'requested' => $cantidad,
                    'missing' => $cantidadRestante,
                ]
            ]);
        }
    }
    
    /**
     * Obtener factura por ID
     */
    public function getSaleById(int $id): Factura
    {
        $factura = Factura::with(['productos', 'estadoFactura'])->find($id);
        
        if (!$factura) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Factura #{$id} no encontrada"
            );
        }
        
        return $factura;
    }
    
    /**
     * Anular factura y restaurar stock
     */
    public function cancelSale(int $id, string $reason): Factura
    {
        return DB::transaction(function() use ($id, $reason) {
            $factura = $this->getSaleById($id);
            
            // Verificar que no esté ya anulada
            if ($factura->estado_factura_id == 3) { // 3 = anulada
                throw new \Exception('Esta factura ya está anulada');
            }
            
            // Obtener items de la factura
            $items = FacturaHasProducto::where('factura_id', $id)->get();
            
            // Restaurar stock
            foreach ($items as $item) {
                $this->restaurarStockBodega($item->producto_id, $item->cantidad);
            }
            
            // Marcar factura como anulada
            $factura->estado_factura_id = 3; // 3 = anulada
            $factura->comentario = ($factura->comentario ?? '') . " | ANULADA: {$reason}";
            $factura->save();
            
            // Invalidar cache
            Cache::flush(); // Driver 'file' no soporta tags
            
            Log::info('API: Factura anulada y stock restaurado', [
                'factura_id' => $id,
                'reason' => $reason,
                'items' => $items->count(),
            ]);
            
            return $factura->fresh(['productos']);
        });
    }
    
    /**
     * Restaurar stock a bodega (se agrega al lote más reciente)
     */
    private function restaurarStockBodega(int $productoId, int $cantidad): void
    {
        // Obtener el lote más reciente del producto
        $lote = RecibidoBodega::where('producto_id', $productoId)
            ->where('estado_id', 1)
            ->orderBy('fecha_recibido', 'desc')
            ->lockForUpdate()
            ->first();
        
        if ($lote) {
            $lote->cantidad_disponible += $cantidad;
            $lote->save();
            
            Log::info('Stock restaurado a bodega', [
                'lote_id' => $lote->id,
                'producto_id' => $productoId,
                'cantidad_restaurada' => $cantidad,
                'nuevo_total' => $lote->cantidad_disponible,
            ]);
        } else {
            // Si no hay lotes, crear uno nuevo
            RecibidoBodega::create([
                'producto_id' => $productoId,
                'seccion_id' => 1,
                'cantidad_compra_lote' => $cantidad,
                'cantidad_inicial_seccion' => $cantidad,
                'cantidad_disponible' => $cantidad,
                'fecha_recibido' => now(),
                'comentario' => 'Restaurado por anulación de factura',
                'unidad_medida_id' => 1,
                'users_registro_id' => 1,
                'estado_id' => 1,
            ]);
        }
    }
    
    /**
     * Sincronizar cambio de stock con página web
     * Se llama después de descontar stock durante facturación
     */
    private function sincronizarInventarioWeb(int $productoId, int $cantidadVendida): void
    {
        try {
            // Obtener nombre del producto
            $producto = DB::table('producto')->where('id', $productoId)->first(['nombre']);
            
            if (!$producto) {
                Log::warning('Producto no encontrado para sincronización web', [
                    'producto_id' => $productoId
                ]);
                return;
            }
            
            // Calcular stock actual después del descuento
            $stockActual = RecibidoBodega::where('producto_id', $productoId)
                ->where('estado_id', 1)
                ->where('cantidad_disponible', '>', 0)
                ->sum('cantidad_disponible');
            
            // Stock anterior era el actual + lo que se vendió
            $stockAnterior = $stockActual + $cantidadVendida;
            
            // Sincronizar con página web
            $this->syncService->sincronizarCambioStock(
                $productoId,
                $producto->nombre,
                (int) $stockAnterior,
                (int) $stockActual,
                'factura_web',
                [
                    'cantidad_vendida' => $cantidadVendida,
                    'tipo_operacion' => 'venta_web'
                ]
            );
            
            Log::info('Stock sincronizado con página web', [
                'producto_id' => $productoId,
                'producto_nombre' => $producto->nombre,
                'stock_anterior' => $stockAnterior,
                'stock_actual' => $stockActual,
                'cantidad_vendida' => $cantidadVendida
            ]);
            
        } catch (\Exception $e) {
            // No fallar la facturación si hay error en la sincronización
            Log::error('Error al sincronizar inventario con página web', [
                'producto_id' => $productoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
