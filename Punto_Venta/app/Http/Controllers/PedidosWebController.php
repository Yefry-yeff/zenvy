<?php

namespace App\Http\Controllers;

use App\Models\PedidoWeb;
use App\Services\Api\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PedidosWebController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    
    /**
     * Mostrar bandeja de entrada de pedidos web
     */
    public function index()
    {
        $pedidos = PedidoWeb::with(['items.producto'])
            ->orderBy('leido', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('pedidos-web.index', compact('pedidos'));
    }
    
    /**
     * Ver detalle de un pedido
     */
    public function show($id)
    {
        $pedido = PedidoWeb::with(['items.producto', 'factura'])->findOrFail($id);
        
        // Marcar como leído
        if (!$pedido->leido) {
            $pedido->marcarComoLeido();
        }
        
        return view('pedidos-web.show', compact('pedido'));
    }
    
    /**
     * Procesar pedido y crear factura
     */
    public function process(Request $request, $id)
    {
        try {
            $factura = $this->orderService->convertirPedidoAFactura(
                $id,
                Auth::id(),
                [
                    'caja_id' => $request->input('caja_id', 1),
                    'cai_id' => $request->input('cai_id', 1),
                ]
            );
            
            return redirect()
                ->route('pedidos-web.show', $id)
                ->with('success', "Pedido procesado. Factura #{$factura->id} creada exitosamente.");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar pedido: ' . $e->getMessage());
        }
    }
    
    /**
     * Rechazar pedido
     */
    public function reject($id)
    {
        $pedido = PedidoWeb::findOrFail($id);
        $pedido->rechazar();
        
        return back()->with('success', 'Pedido rechazado');
    }
    
    /**
     * API: Obtener pedidos pendientes para el dropdown
     */
    public function apiPendientes()
    {
        $pedidos = PedidoWeb::pendientes()
            ->with('items')
            ->orderBy('leido', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $pedidos->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente_nombre' => $pedido->cliente_nombre,
                    'total' => number_format($pedido->total, 2),
                    'items_count' => $pedido->items->count(),
                    'leido' => $pedido->leido,
                    'created_at' => $pedido->created_at->diffForHumans(),
                    'fecha_pedido' => $pedido->created_at->format('d/m/Y H:i'),
                    'url' => route('pedidos-web.show', $pedido->id),
                ];
            })
        ]);
    }
    
    /**
     * API: Contar pedidos no leídos
     */
    public function unreadCount()
    {
        $count = PedidoWeb::pendientesNoLeidos()->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }
}
