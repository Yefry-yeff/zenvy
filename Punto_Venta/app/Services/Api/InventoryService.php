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
     * 
     * @param array $items Items a validar
     * @param int|null $excludePedidoId ID del pedido cuyas reservas deben excluirse (para permitir facturación del propio pedido)
     */
    public function validateStock(array $items, ?int $excludePedidoId = null): array
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
                
                // Obtener reservas activas, excluyendo las del pedido actual si se especifica
                $reservasQuery = \DB::table('reservas_inventario')
                    ->where('producto_id', $product->id)
                    ->where('estado', 'activa');
                
                if ($excludePedidoId) {
                    $reservasQuery->where('pedido_web_id', '!=', $excludePedidoId);
                }
                
                $reservas = $reservasQuery->sum('cantidad_reservada');
                
                // Stock disponible real = stock - reservas (excluyendo reservas del pedido actual)
                $stockDisponible = max(0, $stockReal - $reservas);
                
                $isAvailable = $stockDisponible >= $item['quantity'];
                
                $result['items'][] = [
                    'sku' => (string) $product->id,
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'requested' => $item['quantity'],
                    'available' => (int) $stockDisponible,
                    'stock_total' => (int) $stockReal,
                    'reservado' => (int) $reservas,
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
                'codigo_estatal' => $product->codigo_barra,
                'nombre' => $product->nombre,
                'stock_actual' => $product->stock_actual,
                'stock_minimo' => $product->stock_minimo,
                'categoria' => $product->categoria?->nombre,
            ];
        })->toArray();
    }

    /**
     * Obtener todos los productos con stock agrupados por categoría (Segmento)
     * Agrupa productos por código de barras + unidad de medida
     * Si un producto tiene la misma unidad y código de barras, suma el stock
     */
    public function getProductsByCategory(): array
    {
        $cacheKey = 'inventory_by_category';
        $cacheTtl = config('api.inventory.cache_ttl', 300); // 5 minutos por defecto
        
        return Cache::remember($cacheKey, $cacheTtl, function() {
            // Obtener productos con stock > 0 desde recibido_bodega
            // Agrupado por código de barras (precio_has_venta) + unidad de medida
            $products = \DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->join('precio_has_venta as phv', 'rb.precio_venta_id', '=', 'phv.id')
                ->join('unidad_medida as um', 'phv.unidad_medida_id', '=', 'um.id')
                ->join('seccion as sec', 'rb.seccion_id', '=', 'sec.id')
                ->join('segmento as seg', 'sec.segmento_id', '=', 'seg.id')
                ->select([
                    'p.id as producto_id',
                    'p.nombre as producto_nombre',
                    'phv.codigo_barra',
                    'phv.id as precio_venta_id',
                    'phv.descripcion as presentacion',
                    'phv.cantidad as cantidad_por_unidad',
                    'phv.precio',
                    'um.id as unidad_medida_id',
                    'um.nombre as unidad_nombre',
                    'um.simbolo as unidad_simbolo',
                    'seg.id as categoria_id',
                    'seg.descripcion as categoria_nombre',
                    \DB::raw('SUM(rb.cantidad_disponible) as stock_disponible')
                ])
                ->where('rb.estado_id', '=', 1)
                ->where('p.estado_id', '=', 1)
                ->where('phv.estado_id', '=', 1)
                ->groupBy(
                    'p.id', 'p.nombre',
                    'phv.codigo_barra', 'phv.id', 'phv.descripcion', 'phv.cantidad', 'phv.precio',
                    'um.id', 'um.nombre', 'um.simbolo',
                    'seg.id', 'seg.descripcion'
                )
                ->havingRaw('SUM(rb.cantidad_disponible) > 0')
                ->orderBy('seg.descripcion')
                ->orderBy('p.nombre')
                ->orderBy('phv.cantidad')
                ->get();

            // Obtener todas las reservas activas agrupadas por producto_id
            $reservasPorProducto = \DB::table('reservas_inventario')
                ->select('producto_id', \DB::raw('SUM(cantidad_reservada) as total_reservado'))
                ->where('estado', 'activa')
                ->groupBy('producto_id')
                ->pluck('total_reservado', 'producto_id')
                ->toArray();

            // Agrupar por categoría (Segmento)
            $grouped = [];
            
            foreach ($products as $product) {
                $categoryId = (int)$product->categoria_id;
                
                // Crear categoría si no existe
                if (!isset($grouped[$categoryId])) {
                    $grouped[$categoryId] = [
                        'categoria_id' => $categoryId,
                        'categoria_nombre' => (string)$product->categoria_nombre,
                        'total_productos' => 0,
                        'stock_total' => 0,
                        'productos' => []
                    ];
                }
                
                // Obtener reservas activas del producto
                $reservas = $reservasPorProducto[$product->producto_id] ?? 0;
                
                // Calcular stock disponible real (stock - reservas)
                $stockDisponible = max(0, (int)$product->stock_disponible - $reservas);
                
                // Crear identificador único por código de barras + unidad de medida
                $productData = [
                    'id' => (int)$product->producto_id,
                    'precio_venta_id' => (int)$product->precio_venta_id,
                    'codigo_barra' => (string)$product->codigo_barra,
                    'nombre' => (string)$product->producto_nombre,
                    'presentacion' => (string)($product->presentacion ?? ''),
                    'cantidad_por_unidad' => (int)$product->cantidad_por_unidad,
                    'unidad_medida_id' => (int)$product->unidad_medida_id,
                    'unidad_nombre' => (string)$product->unidad_nombre,
                    'unidad_simbolo' => (string)($product->unidad_simbolo ?? 'ud'),
                    'stock_disponible' => $stockDisponible,
                    'stock_total' => (int)$product->stock_disponible,
                    'stock_reservado' => $reservas,
                    'precio_venta' => (float)$product->precio,
                ];
                
                // Solo incluir productos con stock disponible real > 0
                if ($stockDisponible > 0) {
                    $grouped[$categoryId]['productos'][] = $productData;
                    $grouped[$categoryId]['total_productos']++;
                    $grouped[$categoryId]['stock_total'] += $stockDisponible;
                }
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
