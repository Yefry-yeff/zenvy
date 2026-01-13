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
     * Nota: Esta tabla no maneja stock, retornar vacío
     */
    public function getLowStockProducts(int $threshold = null): \Illuminate\Support\Collection
    {
        // Esta tabla no tiene campos de stock
        // Retornar colección vacía o implementar lógica personalizada
        return collect([]);
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

            // Sin campos de stock, asumir disponibilidad si el producto existe y está activo
            $results[] = [
                'sku' => (string) $product->id,
                'product_id' => $product->id,
                'requested' => $item['quantity'],
                'available_stock' => 9999, // Valor placeholder
                'available' => $product->estado_id == 1,
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
