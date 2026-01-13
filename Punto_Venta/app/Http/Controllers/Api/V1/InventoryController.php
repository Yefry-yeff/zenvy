<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\InventoryService;
use App\Http\Resources\Api\ProductResource;
use App\Http\Requests\Api\ValidateStockRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}
    
    /**
     * Obtener inventario completo o filtrado
     * 
     * GET /api/v1/inventory
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'category_id',
                'available',
                'search',
                'min_stock',
                'order_by',
                'order_direction'
            ]);
            
            $perPage = min(
                $request->get('per_page', config('api.pagination.default_per_page')),
                config('api.pagination.max_per_page')
            );
            
            $products = $this->inventoryService->getInventory($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($products->items()),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * Obtener producto por SKU
     * 
     * GET /api/v1/inventory/{sku}
     */
    public function show(string $sku): JsonResponse
    {
        try {
            $product = $this->inventoryService->getProductBySku($sku);
            
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product)
            ]);
            
        } catch (\App\Exceptions\Api\ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => $e->getMessage(),
                ]
            ], 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Obtener producto por código de barras
     * 
     * GET /api/v1/inventory/barcode/{barcode}
     */
    public function showByBarcode(string $barcode): JsonResponse
    {
        try {
            $product = $this->inventoryService->getProductByBarcode($barcode);
            
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product)
            ]);
            
        } catch (\App\Exceptions\Api\ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => $e->getMessage(),
                ]
            ], 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * Validar disponibilidad de stock
     * 
     * POST /api/v1/inventory/validate-stock
     */
    public function validateStock(ValidateStockRequest $request): JsonResponse
    {
        try {
            $result = $this->inventoryService->validateStock($request->validated()['items']);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Obtener productos con stock bajo
     * 
     * GET /api/v1/inventory/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $threshold = $request->get('threshold');
            $products = $this->inventoryService->getLowStockProducts($threshold);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'meta' => [
                    'count' => count($products),
                    'threshold' => $threshold ?? config('api.inventory.low_stock_threshold')
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * Manejo de errores centralizado
     */
    private function errorResponse(\Exception $e): JsonResponse
    {
        \Log::error('API Inventory Error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Error interno del servidor',
            ]
        ], 500);
    }
}
