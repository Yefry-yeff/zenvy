<?php

namespace App\Repositories;

use App\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /**
     * Obtener productos paginados con filtros
     */
    public function getPaginated(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Producto::query()
            ->with(['subcategoria', 'marca'])
            ->where('estado_id', 1); // 1 = activo
        
        // Filtro por categoría/subcategoría
        if (!empty($filters['category_id'])) {
            $query->where('subcategoria_id', $filters['category_id']);
        }
        
        // Búsqueda por texto
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('codigo_barra', 'like', "%{$search}%")
                  ->orWhere('codigo_estatal', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'nombre';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderBy($orderBy, $orderDirection);
        
        return $query->paginate($perPage);
    }
    
    /**
     * Buscar producto por SKU (usamos ID como SKU)
     */
    public function findBySku(string $sku): ?Producto
    {
        // Intentar buscar por ID o código de barra
        return Producto::where('id', $sku)
            ->orWhere('codigo_barra', $sku)
            ->with(['subcategoria', 'marca'])
            ->first();
    }
    
    /**
     * Buscar producto por SKU con bloqueo (FOR UPDATE)
     */
    public function findBySkuWithLock(string $sku): ?Producto
    {
        return Producto::where('id', $sku)
            ->orWhere('codigo_barra', $sku)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Buscar producto por ID con bloqueo
     */
    public function findWithLock(int $productId): ?Producto
    {
        return Producto::where('id', $productId)
            ->lockForUpdate()
            ->first();
    }
    
    /**
     * Decrementar stock de un producto
     * Nota: Esta tabla no maneja stock directamente
     */
    public function decrementStock(int $productId, int $quantity): bool
    {
        // Retornar true - el stock real se maneja en otras tablas
        return true;
    }
    
    /**
     * Incrementar stock de un producto
     * Nota: Esta tabla no maneja stock directamente
     */
    public function incrementStock(int $productId, int $quantity): bool
    {
        // Retornar true - el stock real se maneja en otras tablas
        return true;
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts(int $threshold = null): \Illuminate\Support\Collection
    {
        $threshold = $threshold ?? config('api.inventory.low_stock_threshold', 10);
        
        // Obtener productos con stock bajo desde recibido_bodega
        $productsLowStock = DB::table('recibido_bodega as rb')
            ->select('rb.producto_id', DB::raw('SUM(rb.cantidad_disponible) as total_stock'))
            ->join('producto as p', 'rb.producto_id', '=', 'p.id')
            ->where('rb.estado_id', 1)
            ->where('p.estado_id', 1)
            ->groupBy('rb.producto_id')
            ->having('total_stock', '<=', $threshold)
            ->having('total_stock', '>', 0)
            ->pluck('producto_id');
        
        if ($productsLowStock->isEmpty()) {
            return collect([]);
        }
        
        return Producto::whereIn('id', $productsLowStock)
            ->with(['subcategoria', 'marca'])
            ->get();
    }

    /**
     * Validar disponibilidad de múltiples productos
     */
    public function checkStockAvailability(array $items): array
    {
        $results = [];
        
        foreach ($items as $item) {
            $product = $this->findBySku($item['sku']);
            
            if (!$product) {
                $results[] = [
                    'sku' => $item['sku'],
                    'available' => false,
                    'reason' => 'product_not_found',
                ];
                continue;
            }

            // Obtener stock real desde recibido_bodega
            $totalStock = DB::table('recibido_bodega')
                ->where('producto_id', $product->id)
                ->where('estado_id', 1)
                ->where('cantidad_disponible', '>', 0)
                ->sum('cantidad_disponible');

            $results[] = [
                'sku' => (string) $product->id,
                'product_id' => $product->id,
                'requested' => $item['quantity'],
                'available_stock' => (int) $totalStock,
                'available' => $totalStock >= $item['quantity'],
            ];
        }
        
        return $results;
    }

    /**
     * Obtener producto por código de barras
     */
    public function findByBarcode(string $barcode): ?Producto
    {
        return Producto::where('codigo_barra', $barcode)
            ->where('estado_id', 1)
            ->with(['subcategoria', 'marca'])
            ->first();
    }
}
