<?php

namespace App\Services\Api;

use App\Repositories\ProductRepository;
use App\Exceptions\Api\ProductNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryService
{
    public function __construct(
        private ProductRepository $productRepo
    ) {}
    
    /**
     * Obtener inventario con filtros y caché
     */
    public function getInventory(array $filters, int $perPage): LengthAwarePaginator
    {
        $cacheKey = 'inventory_' . md5(json_encode($filters) . $perPage);
        $cacheTtl = config('api.inventory.cache_ttl');
        
        return Cache::remember($cacheKey, $cacheTtl, function() use ($filters, $perPage) {
            return $this->productRepo->getPaginated($filters, $perPage);
        });
    }
    
    /**
     * Obtener producto por SKU
     */
    public function getProductBySku(string $sku): object
    {
        $product = Cache::remember(
            "product_sku_{$sku}",
            600,
            fn() => $this->productRepo->findBySku($sku)
        );
        
        if (!$product) {
            throw new ProductNotFoundException("Producto con SKU {$sku} no encontrado");
        }
        
        return $product;
    }

    /**
     * Obtener producto por código de barras
     */
    public function getProductByBarcode(string $barcode): object
    {
        $product = Cache::remember(
            "product_barcode_{$barcode}",
            600,
            fn() => $this->productRepo->findByBarcode($barcode)
        );
        
        if (!$product) {
            throw new ProductNotFoundException("Producto con código de barras {$barcode} no encontrado");
        }
        
        return $product;
    }
    
    /**
     * Validar disponibilidad de stock para múltiples productos
     */
    public function validateStock(array $items): array
    {
        $result = [
            'available' => true,
            'items' => []
        ];
        
        foreach ($items as $item) {
            try {
                $product = $this->getProductBySku($item['sku']);
                $isAvailable = $product->stock_actual >= $item['quantity'];
                
                $result['items'][] = [
                    'sku' => $item['sku'],
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'requested' => $item['quantity'],
                    'available' => $product->stock_actual,
                    'is_available' => $isAvailable
                ];
                
                if (!$isAvailable) {
                    $result['available'] = false;
                }
            } catch (ProductNotFoundException $e) {
                $result['items'][] = [
                    'sku' => $item['sku'],
                    'requested' => $item['quantity'],
                    'available' => 0,
                    'is_available' => false,
                    'error' => 'Producto no encontrado'
                ];
                $result['available'] = false;
            }
        }
        
        return $result;
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts(int $threshold = null): array
    {
        $products = $this->productRepo->getLowStockProducts($threshold);
        
        return $products->map(function($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'nombre' => $product->nombre,
                'stock_actual' => $product->stock_actual,
                'stock_minimo' => $product->stock_minimo,
                'categoria' => $product->categoria?->nombre,
            ];
        })->toArray();
    }

    /**
     * Invalidar caché de inventario
     */
    public function invalidateCache(): void
    {
        Cache::tags(['inventory', 'products'])->flush();
    }
}
