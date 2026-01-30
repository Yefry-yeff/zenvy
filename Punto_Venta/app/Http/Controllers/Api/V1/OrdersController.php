<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateOrderRequest;
use App\Services\Api\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de Pedidos Web
 * 
 * Los pedidos no crean facturas automáticamente.
 * Quedan en estado "pendiente" hasta que se procesen manualmente en Zenvy.
 */
class OrdersController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    
    /**
     * Crear nuevo pedido web
     * 
     * POST /api/v1/orders
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            // Log para debugging
            \Log::info('API Order Received - RAW DATA', [
                'all_input' => $request->all(),
                'has_transfer_info' => $request->has('transfer_info'),
                'transfer_info_value' => $request->input('transfer_info'),
            ]);
            
            $pedido = $this->orderService->createOrder(
                $request->validated(),
                $request->api_client
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $pedido->estado,
                    'cliente' => $pedido->cliente_nombre,
                    'total' => (float) $pedido->total,
                    'items_count' => $pedido->items->count(),
                    'created_at' => $pedido->created_at,
                ],
                'message' => 'Pedido recibido. Será procesado por el equipo de Zenvy.'
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_CREATION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }
    
    /**
     * Listar pedidos pendientes (bandeja de entrada)
     * 
     * GET /api/v1/orders/pending
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);
            $pedidos = $this->orderService->getPedidosPendientes($limit);
            
            return response()->json([
                'success' => true,
                'data' => $pedidos->map(function($pedido) {
                    return [
                        'id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'estado' => $pedido->estado,
                        'leido' => $pedido->leido,
                        'cliente' => [
                            'nombre' => $pedido->cliente_nombre,
                            'email' => $pedido->cliente_email,
                            'telefono' => $pedido->cliente_telefono,
                        ],
                        'total' => (float) $pedido->total,
                        'items_count' => $pedido->items->count(),
                        'created_at' => $pedido->created_at,
                    ];
                }),
                'total' => $pedidos->count(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }
    
    /**
     * Obtener detalle de un pedido
     * 
     * GET /api/v1/orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $pedido = \App\Models\PedidoWeb::with('items.producto')->findOrFail($id);
            
            // Marcar como leído si está pendiente
            if (!$pedido->leido) {
                $pedido->marcarComoLeido();
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $pedido->estado,
                    'leido' => $pedido->leido,
                    'cliente' => [
                        'nombre' => $pedido->cliente_nombre,
                        'email' => $pedido->cliente_email,
                        'telefono' => $pedido->cliente_telefono,
                        'rtn' => $pedido->cliente_rtn,
                        'direccion' => $pedido->cliente_direccion,
                    ],
                    'items' => $pedido->items->map(function($item) {
                        return [
                            'producto_id' => $item->producto_id,
                            'producto_nombre' => $item->producto->nombre_producto ?? 'N/A',
                            'cantidad' => $item->cantidad,
                            'precio_unitario' => (float) $item->precio_unitario,
                            'subtotal' => (float) $item->subtotal,
                            'isv' => (float) $item->isv,
                            'total' => (float) $item->total,
                        ];
                    }),
                    'subtotal' => (float) $pedido->subtotal,
                    'descuento' => (float) $pedido->descuento,
                    'isv' => (float) $pedido->isv,
                    'total' => (float) $pedido->total,
                    'metodo_pago' => $pedido->metodo_pago,
                    'notas' => $pedido->notas,
                    'metadata' => $pedido->metadata,
                    'factura_id' => $pedido->factura_id,
                    'fecha_facturado' => $pedido->fecha_facturado,
                    'created_at' => $pedido->created_at,
                    'updated_at' => $pedido->updated_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => $e->getMessage(),
                ]
            ], 404);
        }
    }
    
    /**
     * Contar pedidos no leídos (notificaciones)
     * 
     * GET /api/v1/orders/unread/count
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = $this->orderService->contarPedidosNoLeidos();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COUNT_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }
    
    /**
     * Convertir pedido a factura (procesar pedido)
     * 
     * POST /api/v1/orders/{id}/process
     */
    public function process(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $request->input('user_id', 1); // ID del usuario de Zenvy que procesa
            $options = $request->input('options', []);
            
            $factura = $this->orderService->convertirPedidoAFactura($id, $userId, $options);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'factura_id' => $factura->id,
                    'pedido_id' => $id,
                    'total' => (float) $factura->total,
                    'estado' => 'facturado',
                ],
                'message' => 'Pedido procesado y factura creada exitosamente'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PROCESS_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }
}
