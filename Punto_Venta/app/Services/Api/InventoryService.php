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
                
                // Obtener stock real desde recibido_bodega
                $stockReal = \DB::table('recibido_bodega')
                    ->where('producto_id', $product->id)
                    ->where('estado_id', 1)
                    ->where('cantidad_disponible', '>', 0)
                    ->sum('cantidad_disponible');
                
                $isAvailable = $stockReal >= $item['quantity'];
                
                $result['items'][] = [
                    'sku' => (string) $product->id,
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'requested' => $item['quantity'],
                    'available' => (int) $stockReal,
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
     * Obtener todos los productos con stock agrupados por categoría
     * Ideal para sincronización en tiempo real con el frontend
     */
    public function getProductsByCategory(): array
    {
        $cacheKey = 'inventory_by_category';
        $cacheTtl = config('api.inventory.cache_ttl', 300); // 5 minutos por defecto
        
        return Cache::remember($cacheKey, $cacheTtl, function() {
            // Obtener productos con stock > 0 junto con su categoría
            $products = \DB::table('producto as p')
                ->select([
                    'p.id',
                    'p.sku',
                    'p.codigo_barra',
                    'p.nombre',
                    'p.precio_venta',
                    'p.precio_compra',
                    'p.categoria_id',
                    'c.nombre as categoria_nombre',
                    'c.descripcion as categoria_descripcion',
                    \DB::raw('COALESCE(SUM(rb.cantidad_disponible), 0) as stock_disponible')
                ])
                ->leftJoin('categoria as c', 'p.categoria_id', '=', 'c.id')
                ->leftJoin('recibido_bodega as rb', function($join) {
                    $join->on('p.id', '=', 'rb.producto_id')
                         ->where('rb.estado_id', '=', 1); // Solo disponibles
                })
                ->where('p.estado', '=', 'activo')
                ->groupBy('p.id', 'p.sku', 'p.codigo_barra', 'p.nombre', 'p.precio_venta', 
                         'p.precio_compra', 'p.categoria_id', 'c.nombre', 'c.descripcion')
                ->having(\DB::raw('COALESCE(SUM(rb.cantidad_disponible), 0)'), '>', 0)
                ->get();

            // Agrupar por categoría
            $grouped = [];
            
            foreach ($products as $product) {
                $categoryId = $product->categoria_id ?? 0;
                $categoryName = $product->categoria_nombre ?? 'Sin Categoría';
                
                if (!isset($grouped[$categoryId])) {
                    $grouped[$categoryId] = [
                        'categoria_id' => $categoryId,
                        'categoria_nombre' => $categoryName,
                        'categoria_descripcion' => $product->categoria_descripcion ?? '',
                        'total_productos' => 0,
                        'stock_total' => 0,
                        'productos' => []
                    ];
                }
                
                $productData = [
                    'id' => (int)$product->id,
                    'sku' => (string)$product->sku,
                    'codigo_barra' => (string)$product->codigo_barra,
                    'nombre' => (string)$product->nombre,
                    'stock' => (int)$product->stock_disponible,
                    'precio_venta' => (float)$product->precio_venta,
                    'precio_compra' => (float)$product->precio_compra,
                    'margen' => $product->precio_compra > 0 
                        ? round((($product->precio_venta - $product->precio_compra) / $product->precio_compra) * 100, 2)
                        : 0
                ];
                
                $grouped[$categoryId]['productos'][] = $productData;
                $grouped[$categoryId]['total_productos']++;
                $grouped[$categoryId]['stock_total'] += (int)$product->stock_disponible;
            }
            
            // Retornar como array indexado (compatible con JSON)
            return array_values($grouped);
        });
    }

    /**
     * Obtener solo categorías con productos disponibles
     */
    public function getCategoriesWithStock(): array
    {
        $cacheKey = 'categories_with_stock';
        $cacheTtl = config('api.inventory.cache_ttl', 300);
        
        return Cache::remember($cacheKey, $cacheTtl, function() {
            $categories = \DB::table('categoria as c')
                ->select([
                    'c.id',
                    'c.nombre',
                    'c.descripcion',
                    \DB::raw('COUNT(DISTINCT p.id) as total_productos'),
                    \DB::raw('COALESCE(SUM(rb.cantidad_disponible), 0) as stock_total')
                ])
                ->leftJoin('producto as p', 'c.id', '=', 'p.categoria_id')
                ->leftJoin('recibido_bodega as rb', function($join) {
                    $join->on('p.id', '=', 'rb.producto_id')
                         ->where('rb.estado_id', '=', 1);
                })
                ->where('p.estado', '=', 'activo')
                ->groupBy('c.id', 'c.nombre', 'c.descripcion')
                ->having(\DB::raw('COALESCE(SUM(rb.cantidad_disponible), 0)'), '>', 0)
                ->orderBy('c.nombre')
                ->get();

            return $categories->map(function($category) {
                return [
                    'id' => (int)$category->id,
                    'nombre' => (string)$category->nombre,
                    'descripcion' => (string)($category->descripcion ?? ''),
                    'total_productos' => (int)$category->total_productos,
                    'stock_total' => (int)$category->stock_total
                ];
            })->toArray();
        });
    }

    /**
     * Invalidar caché de inventario
     */
    public function invalidateCache(): void
    {
        Cache::flush(); // Driver 'file' no soporta tags
    }
}
