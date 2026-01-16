<?php

namespace App\Services\Api;

use App\Models\PedidoWeb;
use App\Models\PedidoWebItem;
use App\Models\Factura;
use App\Models\FacturaHasProducto;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private ProductRepository $productRepo,
        private InventoryService $inventoryService,
        private \App\Services\CAIService $caiService
    ) {}
    
    /**
     * Crear pedido web (NO crea factura automáticamente)
     */
    public function createOrder(array $data, $apiClient): PedidoWeb
    {
        return DB::transaction(function() use ($data, $apiClient) {
            // 1. Validar disponibilidad de stock (opcional, solo advertencia)
            $stockValidation = $this->inventoryService->validateStock($data['items']);
            
            // 2. Generar número de pedido único
            $numeroPedido = $data['order_number'] ?? $this->generateOrderNumber();
            
            // 3. Crear pedido web
            $pedido = PedidoWeb::create([
                'numero_pedido' => $numeroPedido,
                'estado' => 'pendiente',
                'cliente_nombre' => $data['customer_name'],
                'cliente_email' => $data['customer_email'] ?? null,
                'cliente_telefono' => $data['customer_phone'] ?? null,
                'cliente_rtn' => $data['customer_rtn'] ?? null,
                'cliente_direccion' => $data['customer_address'] ?? null,
                'subtotal' => $data['subtotal'],
                'descuento' => $data['discount'] ?? 0,
                'isv' => $data['tax'],
                'total' => $data['total'],
                'metodo_pago' => $data['payment_method'] ?? null,
                'notas' => $data['notes'] ?? null,
                'metadata' => [
                    'api_client' => $apiClient->name,
                    'stock_disponible_al_crear' => $stockValidation['available'],
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
                'leido' => false,
            ]);
            
            // 4. Crear items del pedido
            foreach ($data['items'] as $item) {
                $product = $this->productRepo->findBySku($item['sku']);
                
                if (!$product) {
                    throw new \Exception("Producto {$item['sku']} no encontrado");
                }
                
                $cantidad = $item['quantity'];
                $precioUnidad = $item['price'];
                $subtotalItem = $cantidad * $precioUnidad;
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
            
            Log::info('API: Pedido web recibido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'total' => $pedido->total,
                'items' => count($data['items']),
                'api_client' => $apiClient->name,
            ]);
            
            return $pedido->load('items.producto');
        });
    }
    
    /**
     * Convertir pedido web en factura
     * (Este método se llamaría desde el UI de Zenvy cuando aprueban el pedido)
     */
    public function convertirPedidoAFactura(int $pedidoId, int $userId, array $options = []): Factura
    {
        return DB::transaction(function() use ($pedidoId, $userId, $options) {
            $pedido = PedidoWeb::with('items.producto')->findOrFail($pedidoId);
            
            if ($pedido->estado !== 'pendiente') {
                throw new \Exception("El pedido ya fue procesado");
            }
            
            // Marcar como procesando
            $pedido->marcarComoProcesando($userId);
            
            // Validar stock nuevamente al momento de facturar
            $items = $pedido->items->map(fn($item) => [
                'sku' => $item->producto_id,
                'quantity' => $item->cantidad,
            ])->toArray();
            
            $stockValidation = $this->inventoryService->validateStock($items);
            
            if (!$stockValidation['available']) {
                $pedido->update(['estado' => 'pendiente']); // Revertir
                throw new \Exception("Stock insuficiente para procesar el pedido");
            }
            
            // Crear transacción
            $transaccionId = DB::table('transaccion')->insertGetId([
                'caja_id' => $options['caja_id'] ?? 1,
            ]);
            
            // Obtener el siguiente número de factura del CAI
            try {
                $infoCAI = $this->caiService->obtenerSiguienteNumeroFactura();
                $caiId = $infoCAI['cai_id'];
                $numeroFactura = $infoCAI['numero_factura'];
            } catch (\Exception $e) {
                Log::error('Error al obtener CAI', ['error' => $e->getMessage()]);
                throw new \Exception('No se pudo obtener un CAI válido para facturar: ' . $e->getMessage());
            }
            
            // Crear factura
            $factura = Factura::create([
                'cai_id' => $caiId,
                'numero_factura' => $numeroFactura,
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
                'comentario' => "Pedido Web #{$pedido->numero_pedido}" . ($pedido->notas ? " - {$pedido->notas}" : ""),
                'porc_descuento' => 0,
                'monto_descuento' => $pedido->descuento,
                'precio_dolar' => 1,
                'estado_factura_id' => 1,
                'tipo_facturacion_id' => $options['tipo_facturacion_id'] ?? 1,
                'users_id' => $userId,
                'origen_web' => true, // Bandera para identificar que viene de la web
            ]);
            
            // Crear items de factura y descontar stock
            $indice = 1;
            foreach ($pedido->items as $item) {
                FacturaHasProducto::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $item->producto_id,
                    'seccion_id' => 2,
                    'unidad_medida_id' => $item->producto->unidad_medida_venta_id ?? 9,
                    'precio_id' => 1,
                    'indice' => $indice++,
                    'numero_unidades_resta_inventario' => $item->cantidad,
                    'resta_inventario_total' => $item->cantidad,
                    'precio_unidad' => $item->precio_unitario,
                    'cantidad' => $item->cantidad,
                    'subtotal' => $item->subtotal,
                    'descuento' => 0,
                    'isv_aplicado' => 15,
                    'isv' => $item->isv,
                    'total' => $item->total,
                    'idPrecioSeleccionado' => 1,
                    'precio_seleccionado' => $item->precio_unitario,
                ]);
                
                // Descontar stock usando FIFO
                $this->decrementarStockBodega($item->producto_id, $item->cantidad);
            }
            
            // Crear registro de pago basado en el método de pago del pedido web
            $tipoPagoId = $this->obtenerTipoPagoId($pedido->metodo_pago);
            \App\Models\FacturaHasPago::create([
                'factura_id' => $factura->id,
                'tipo_pago_id' => $tipoPagoId,
                'total_factura' => $pedido->total,
                'pago_recibido' => $pedido->total,
                'cambio' => 0
            ]);
            
            // Marcar pedido como facturado
            $pedido->marcarComoFacturado($factura->id);
            
            Log::info('API: Pedido web convertido a factura', [
                'pedido_id' => $pedido->id,
                'factura_id' => $factura->id,
                'usuario_id' => $userId,
            ]);
            
            return $factura->load('productos');
        });
    }
    
    /**
     * Obtener pedidos pendientes (bandeja de entrada)
     */
    public function getPedidosPendientes(int $limit = 50)
    {
        return PedidoWeb::with(['items.producto'])
            ->pendientes()
            ->orderBy('leido', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Contar pedidos no leídos (para notificaciones)
     */
    public function contarPedidosNoLeidos(): int
    {
        return PedidoWeb::pendientesNoLeidos()->count();
    }
    
    /**
     * Decrementar stock en bodega usando FIFO
     */
    private function decrementarStockBodega(int $productoId, int $cantidadRequerida): void
    {
        $cantidadRestante = $cantidadRequerida;
        
        $lotes = DB::table('recibido_bodega')
            ->where('producto_id', $productoId)
            ->where('estado_id', 1)
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha_recibido', 'asc')
            ->lockForUpdate()
            ->get();
        
        foreach ($lotes as $lote) {
            if ($cantidadRestante <= 0) break;
            
            $cantidadADescontar = min($lote->cantidad_disponible, $cantidadRestante);
            
            DB::table('recibido_bodega')
                ->where('id', $lote->id)
                ->decrement('cantidad_disponible', $cantidadADescontar);
            
            $cantidadRestante -= $cantidadADescontar;
        }
        
        if ($cantidadRestante > 0) {
            throw new \Exception("Stock insuficiente: faltan {$cantidadRestante} unidades del producto {$productoId}");
        }
    }
    
    /**
     * Generar número de pedido único
     */
    private function generateOrderNumber(): string
    {
        return 'WEB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    /**
     * Obtener ID del tipo de pago basado en el método
     */
    private function obtenerTipoPagoId(?string $metodoPago): int
    {
        // Mapeo de métodos de pago del pedido web a tipo_pago_id
        // Basado en tabla tipo_pago: 1=Efectivo, 2=Tarjeta(POS), 3=Cheque, 4=Transferencia
        $mapeo = [
            'efectivo' => 1,
            'tarjeta' => 2,
            'transferencia' => 4,
            'cheque' => 3,
        ];
        
        $metodo = strtolower($metodoPago ?? 'efectivo');
        return $mapeo[$metodo] ?? 1; // Por defecto efectivo
    }
}
