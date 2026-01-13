<?php

namespace App\Services\Api;

use App\Repositories\SaleRepository;
use App\Repositories\ProductRepository;
use App\Models\Factura;
use App\Models\SaleStatus;
use App\Exceptions\Api\InsufficientStockException;
use App\Exceptions\Api\ProductNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SalesService
{
    public function __construct(
        private SaleRepository $saleRepo,
        private ProductRepository $productRepo
    ) {}
    
    /**
     * Crear venta con control de concurrencia
     */
    public function createSale(array $data, $apiClient): Factura
    {
        // Lock para evitar ventas concurrentes del mismo pedido
        $lockKey = "sale_creation_{$data['external_order_id']}";
        $lock = Cache::lock($lockKey, 10);
        
        try {
            // Intentar obtener el lock
            if (!$lock->get()) {
                throw new \Exception('Esta venta ya está siendo procesada');
            }
            
            // Verificar si ya existe factura para este pedido
            $existingSale = $this->saleRepo->findByExternalOrderId($data['external_order_id']);
                
            if ($existingSale) {
                throw new \Exception('Este pedido ya fue facturado previamente');
            }
            
            // Transacción para garantizar atomicidad
            return DB::transaction(function() use ($data, $apiClient) {
                // 1. Validar y bloquear inventario
                $this->validateAndLockStock($data['items']);
                
                // 2. Crear factura
                $factura = $this->saleRepo->create([
                    'external_order_id' => $data['external_order_id'],
                    'cliente_documento' => $data['cliente']['documento'] ?? null,
                    'cliente_nombre' => $data['cliente']['nombre'],
                    'cliente_email' => $data['cliente']['email'] ?? null,
                    'cliente_telefono' => $data['cliente']['telefono'] ?? null,
                    'cliente_direccion' => $data['cliente']['direccion'] ?? null,
                    'subtotal' => $data['subtotal'],
                    'descuento' => $data['descuento'] ?? 0,
                    'impuestos' => $data['impuestos'] ?? 0,
                    'total' => $data['total'],
                    'forma_pago' => $data['forma_pago'] ?? 'web',
                    'estado' => SaleStatus::COMPLETED->value,
                    'metadatos' => json_encode($data['metadatos'] ?? []),
                    'api_client_id' => $apiClient->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // 3. Crear items de factura y descontar inventario
                foreach ($data['items'] as $item) {
                    $product = $this->productRepo->findBySku($item['sku']);
                    
                    // Crear detalle de factura
                    DB::table('factura_has_producto')->insert([
                        'factura_id' => $factura->id,
                        'producto_id' => $product->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                        'created_at' => now(),
                    ]);
                    
                    // Descontar inventario
                    $this->productRepo->decrementStock(
                        $product->id,
                        $item['cantidad']
                    );
                }
                
                // 4. Invalidar cache de inventario
                $this->invalidateInventoryCache();
                
                // 5. Log de éxito
                Log::info('Venta creada exitosamente', [
                    'factura_id' => $factura->id,
                    'external_order_id' => $data['external_order_id'],
                    'total' => $data['total'],
                    'api_client' => $apiClient->name,
                ]);
                
                return $factura->fresh(['items.producto']);
            });
            
        } finally {
            // Liberar lock
            optional($lock)->release();
        }
    }
    
    /**
     * Validar stock con bloqueo pesimista
     */
    private function validateAndLockStock(array $items): void
    {
        $insufficientItems = [];
        
        foreach ($items as $item) {
            // SELECT ... FOR UPDATE para bloquear fila
            $product = $this->productRepo->findBySkuWithLock($item['sku']);
                
            if (!$product) {
                throw new ProductNotFoundException(
                    "Producto con SKU {$item['sku']} no encontrado"
                );
            }
            
            if ($product->stock_actual < $item['cantidad']) {
                $insufficientItems[] = [
                    'sku' => $item['sku'],
                    'product_name' => $product->nombre,
                    'requested' => $item['cantidad'],
                    'available' => $product->stock_actual,
                ];
            }
        }
        
        if (!empty($insufficientItems)) {
            throw new InsufficientStockException($insufficientItems);
        }
    }
    
    /**
     * Obtener venta por ID
     */
    public function getSaleById(int $id): Factura
    {
        $sale = $this->saleRepo->findWithRelations($id);
        
        if (!$sale) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                'Venta no encontrada'
            );
        }
        
        return $sale;
    }
    
    /**
     * Anular una venta
     */
    public function cancelSale(int $id, string $motivo, $apiClient): Factura
    {
        return DB::transaction(function() use ($id, $motivo, $apiClient) {
            $factura = $this->getSaleById($id);
            
            if ($factura->estado === SaleStatus::CANCELLED->value) {
                throw new \Exception('La venta ya está anulada');
            }

            if ($factura->estado === SaleStatus::REFUNDED->value) {
                throw new \Exception('La venta ya fue reembolsada');
            }
            
            // Restaurar inventario
            foreach ($factura->items as $item) {
                $this->productRepo->incrementStock(
                    $item->producto_id,
                    $item->cantidad
                );
            }
            
            // Actualizar estado
            $factura->update([
                'estado' => SaleStatus::CANCELLED->value,
                'motivo_anulacion' => $motivo,
                'anulada_at' => now(),
                'anulada_por_api_client_id' => $apiClient->id,
            ]);
            
            $this->invalidateInventoryCache();
            
            Log::info('Venta anulada', [
                'factura_id' => $factura->id,
                'motivo' => $motivo,
                'api_client' => $apiClient->name,
            ]);
            
            return $factura;
        });
    }
    
    /**
     * Invalidar caché de inventario
     */
    private function invalidateInventoryCache(): void
    {
        Cache::tags(['inventory', 'products'])->flush();
    }
}
