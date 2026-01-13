<?php

namespace App\Services\Api;

use App\Repositories\ProductRepository;
use App\Models\Factura;
use App\Models\FacturaHasProducto;
use App\Models\RecibidoBodega;
use App\Exceptions\Api\InsufficientStockException;
use App\Exceptions\Api\ProductNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SalesService
{
    public function __construct(
        private ProductRepository $productRepo
    ) {}
    
    /**
     * Crear venta/factura con control de stock
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
                
                // 3. Crear factura principal
                $factura = Factura::create([
                    'cai_id' => 1, // CAI por defecto - ajustar según configuración
                    'transaccion_id' => $transaccionId,
                    'nombre_cliente' => $data['customer_name'] ?? 'Cliente Web',
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
                    'comentario' => $data['notes'] ?? 'Venta desde API - E-commerce',
                    'porc_descuento' => 0,
                    'monto_descuento' => $data['discount'] ?? 0,
                    'precio_dolar' => 1,
                    'estado_factura_id' => 1, // 1 = completada
                    'tipo_facturacion_id' => 1, // Ajustar según tu configuración
                    'users_id' => 1, // Usuario del sistema - ajustar si es necesario
                ]);
                
                // 4. Crear items de factura y descontar stock
                $indice = 1;
                foreach ($data['items'] as $item) {
                    $product = $this->productRepo->findBySku($item['sku']);
                    
                    if (!$product) {
                        throw new ProductNotFoundException("Producto {$item['sku']} no encontrado");
                    }
                    
                    $cantidad = $item['quantity'];
                    $precioUnidad = $item['price'];
                    $subtotalItem = $cantidad * $precioUnidad;
                    $isvItem = $subtotalItem * 0.15; // 15% ISV
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
                        'descuento' => 0,
                        'isv_aplicado' => 15.00,
                        'isv' => $isvItem,
                        'total' => $totalItem,
                        'idPrecioSeleccionado' => '1',
                        'precio_seleccionado' => $precioUnidad,
                    ]);
                    
                    // Descontar stock de recibido_bodega
                    $this->decrementarStockBodega($product->id, $cantidad);
                }
                
                // 4. Invalidar cache de inventario
                Cache::flush(); // Driver 'file' no soporta tags
                
                // 5. Log de éxito
                Log::info('API: Venta creada exitosamente', [
                    'factura_id' => $factura->id,
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
            $product = $this->productRepo->findBySku($item['sku']);
            
            if (!$product) {
                throw new ProductNotFoundException("Producto {$item['sku']} no encontrado");
            }
            
            // Obtener stock total disponible
            $stockDisponible = RecibidoBodega::where('producto_id', $product->id)
                ->where('estado_id', 1)
                ->where('cantidad_disponible', '>', 0)
                ->sum('cantidad_disponible');
            
            if ($stockDisponible < $item['quantity']) {
                $insufficientItems[] = [
                    'sku' => $item['sku'],
                    'product_name' => $product->nombre,
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
}
