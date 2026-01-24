<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    InventoryController,
    SalesController,
    OrdersController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas del API REST para el sistema POS
|
*/

// Ruta de salud del API (sin autenticación)
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente',
        'version' => config('api.version'),
        'timestamp' => now()->toIso8601String()
    ]);
});

// Autenticación - No requiere token
Route::prefix('v1/auth')->group(function() {
    Route::post('token', [AuthController::class, 'generateToken'])->name('api.auth.token');
});

// ========================================
// Webhooks - Sincronización de inventario (sin autenticación API)
// ========================================
Route::post('/webhook/inventory', [InventoryController::class, 'webhookInventory'])->name('api.webhook.inventory');

// Rutas protegidas con autenticación y auditoría
Route::prefix('v1')->middleware([
    'api.auth',
    'api.audit',
    'api.rate.limit'
])->group(function() {
    
    // ========================================
    // Inventario
    // ========================================
    Route::prefix('inventory')->name('api.inventory.')->group(function() {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/by-category', [InventoryController::class, 'byCategory'])->name('by-category');
        Route::get('/categories', [InventoryController::class, 'categories'])->name('categories');
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock');
        Route::post('/validate-stock', [InventoryController::class, 'validateStock'])->name('validate-stock');
        Route::post('/sync/force', [InventoryController::class, 'forceSync'])->name('sync-force');
        Route::get('/barcode/{barcode}', [InventoryController::class, 'showByBarcode'])->name('show-barcode');
        Route::get('/{sku}', [InventoryController::class, 'show'])->name('show');
    });
    
    // ========================================
    // Pedidos Web (Bandeja de Entrada)
    // ========================================
    Route::prefix('orders')->name('api.orders.')->group(function() {
        Route::post('/', [OrdersController::class, 'store'])->name('store');
        Route::get('/pending', [OrdersController::class, 'pending'])->name('pending');
        Route::get('/unread/count', [OrdersController::class, 'unreadCount'])->name('unread-count');
        Route::get('/{id}', [OrdersController::class, 'show'])->name('show');
        Route::post('/{id}/process', [OrdersController::class, 'process'])->name('process');
    });
    
    // ========================================
    // Ventas / Facturación
    // ========================================
    Route::prefix('sales')->name('api.sales.')->group(function() {
        // Nuevo flujo recomendado: preview + process
        Route::post('/preview', [SalesController::class, 'createPreview'])->name('preview');
        Route::post('/{pedidoId}/process', [SalesController::class, 'processPedido'])->name('process');
        
        // Flujo directo (deprecado pero mantenido por compatibilidad)
        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::get('/{id}', [SalesController::class, 'show'])->name('show');
        Route::put('/{id}/cancel', [SalesController::class, 'cancel'])->name('cancel');
    });
});

// Ruta para errores no encontrados
Route::fallback(function(){
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'ROUTE_NOT_FOUND',
            'message' => 'Endpoint no encontrado'
        ]
    ], 404);
});
