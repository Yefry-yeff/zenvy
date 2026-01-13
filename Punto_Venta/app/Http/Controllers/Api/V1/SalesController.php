<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\SalesService;
use App\Http\Requests\Api\CreateSaleRequest;
use App\Http\Resources\Api\SaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesController extends Controller
{
    public function __construct(
        private SalesService $salesService
    ) {}
    
    /**
     * Registrar nueva venta / facturar pedido
     * 
     * POST /api/v1/sales
     */
    public function store(CreateSaleRequest $request): JsonResponse
    {
        try {
            $factura = $this->salesService->createSale(
                $request->validated(),
                $request->api_client
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'factura_id' => $factura->id,
                    'cliente' => $factura->nombre_cliente,
                    'total' => (float) $factura->total,
                    'subtotal' => (float) $factura->sub_total,
                    'isv' => (float) $factura->isv,
                    'fecha_emision' => $factura->fecha_emision,
                    'estado' => $factura->estado_factura_id,
                ],
                'message' => 'Venta registrada exitosamente'
            ], 201);
            
        } catch (\App\Exceptions\Api\InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_STOCK',
                    'message' => 'Stock insuficiente para completar la venta',
                    'details' => $e->getDetails()
                ]
            ], 422);
            
        } catch (\App\Exceptions\Api\ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => $e->getMessage(),
                ]
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error creating sale: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SALE_CREATION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }
    
    /**
     * Consultar estado de una venta
     * 
     * GET /api/v1/sales/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $sale = $this->salesService->getSaleById($id);
            
            return response()->json([
                'success' => true,
                'data' => new SaleResource($sale)
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SALE_NOT_FOUND',
                    'message' => 'Venta no encontrada',
                ]
            ], 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * Anular una venta
     * 
     * PUT /api/v1/sales/{id}/cancel
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => 'required|string|max:500',
            'external_order_id' => 'nullable|string',
        ]);
        
        try {
            $sale = $this->salesService->cancelSale(
                $id,
                $validated['motivo'],
                $request->api_client
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'factura_id' => $sale->id,
                    'numero_factura' => $sale->numero_factura,
                    'estado' => $sale->estado,
                    'motivo_anulacion' => $sale->motivo_anulacion,
                    'anulada_at' => $sale->anulada_at?->toIso8601String(),
                ],
                'message' => 'Venta anulada exitosamente'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SALE_NOT_FOUND',
                    'message' => 'Venta no encontrada',
                ]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANCELLATION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Manejo de errores centralizado
     */
    private function errorResponse(\Exception $e): JsonResponse
    {
        \Log::error('API Sales Error: ' . $e->getMessage(), [
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
