<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    InventoryController,
    SalesController
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
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock');
        Route::post('/validate-stock', [InventoryController::class, 'validateStock'])->name('validate-stock');
        Route::get('/barcode/{barcode}', [InventoryController::class, 'showByBarcode'])->name('show-barcode');
        Route::get('/{sku}', [InventoryController::class, 'show'])->name('show');
    });
    
    // ========================================
    // Ventas / Facturación
    // ========================================
    Route::prefix('sales')->name('api.sales.')->group(function() {
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
