<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\InventoryService;
use App\Services\WebInventorySyncService;
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
     * Obtener todos los productos con stock agrupados por categoría
     * 
     * IMPORTANTE: Los productos se agrupan por código de barras + unidad de medida
     * Si un producto tiene la misma unidad de medida y código de barras en diferentes lotes,
     * el stock se suma automáticamente (ej: 2 unidades + 2 unidades = 4 total)
     * 
     * Ideal para sincronización en tiempo real con el frontend
     * 
     * GET /api/v1/inventory/by-category
     */
    public function byCategory(): JsonResponse
    {
        try {
            $data = $this->inventoryService->getProductsByCategory();
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total_categories' => count($data),
                    'total_products' => collect($data)->sum('total_productos'),
                    'total_stock' => collect($data)->sum('stock_total'),
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Obtener solo categorías con productos disponibles
     * Ideal para actualizar selectores en tiempo real
     * 
     * GET /api/v1/inventory/categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->inventoryService->getCategoriesWithStock();
            
            return response()->json([
                'success' => true,
                'data' => $categories,
                'meta' => [
                    'total_categories' => count($categories),
                    'total_products' => collect($categories)->sum('total_productos'),
                    'total_stock' => collect($categories)->sum('stock_total'),
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * Forzar sincronización de inventario completo con página web
     * 
     * POST /api/v1/inventory/sync/force
     */
    public function forceSync(Request $request): JsonResponse
    {
        try {
            $syncService = app(WebInventorySyncService::class);
            
            if (!$syncService->estaConfigurado()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SYNC_NOT_CONFIGURED',
                        'message' => 'Sincronización no configurada. Configure webhook_url y webhook_token.',
                    ]
                ], 400);
            }
            
            // Obtener inventario actual
            $categories = $this->inventoryService->getProductsByCategory();
            
            // Enviar sincronización
            $result = $syncService->sincronizarInventarioCompleto($categories);
            
            return response()->json([
                'success' => $result,
                'data' => [
                    'synced_at' => now()->toIso8601String(),
                    'total_categories' => count($categories),
                    'total_products' => collect($categories)->sum('total_productos'),
                    'total_stock' => collect($categories)->sum('stock_total'),
                    'webhook_url' => $syncService->obtenerWebhookUrl(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * Receptor de webhooks - Sincronización de inventario desde otra aplicación
     * 
     * POST /api/webhook/inventory
     */
    public function webhookInventory(Request $request): JsonResponse
    {
        try {
            // Validar token del webhook
            $token = str_replace('Bearer ', '', $request->header('Authorization', ''));
            $expectedToken = config('app.webhook_token');
            
            if (!$expectedToken || $token !== $expectedToken) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'WEBHOOK_UNAUTHORIZED',
                        'message' => 'Token de webhook inválido'
                    ]
                ], 401);
            }

            $evento = $request->input('evento');
            
            // Log asincrónico en background (no bloquear respuesta)
            \Log::info('Webhook recibido', [
                'evento' => $evento,
                'timestamp' => $request->input('timestamp'),
                'ip' => $request->ip()
            ]);

            // Responder inmediatamente sin esperar procesamiento
            return response()->json([
                'success' => true,
                'message' => 'Webhook recibido',
                'evento' => $evento
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Webhook error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WEBHOOK_ERROR',
                    'message' => 'Error procesando webhook'
                ]
            ], 500);
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
