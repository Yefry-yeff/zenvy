<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PedidoWeb;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;

class DetallePedido extends Component
{
    public $pedidoId;
    public $pedido;
    public $mostrarModalProcesar = false;
    public $mostrarModalRechazar = false;
    
    // Propiedades para paginación y filtros de productos
    public $registrosPorPaginaProductos = 10;
    public $paginaProductos = 1;
    public $buscarProducto = '';
    public $filtroNombreProducto = '';

    public function mount($pedidoId = null)
    {
        if (!$pedidoId) {
            session()->flash('error', 'ID de pedido no proporcionado');
            $this->dispatch('cambiarVista', 'BandejaPedidos');
            return;
        }

        $this->pedidoId = $pedidoId;
        $this->cargarPedido();
    }

    public function cargarPedido()
    {
        $this->pedido = PedidoWeb::with(['items.producto', 'procesadoPor'])
            ->find($this->pedidoId);

        if (!$this->pedido) {
            session()->flash('error', 'Pedido no encontrado');
            $this->dispatch('cambiarVista', 'BandejaPedidos');
            return;
        }

        // Marcar como leído
        if (!$this->pedido->leido) {
            $this->pedido->marcarComoLeido();
        }
    }

    public function procesarPedido()
    {
        try {
            $this->mostrarModalProcesar = false;
            
            $orderService = app(\App\Services\Api\OrderService::class);
            $factura = $orderService->convertirPedidoAFactura(
                $this->pedidoId,
                auth()->id()
            );

            session()->flash('success', "Pedido procesado exitosamente. Factura ID: {$factura->id}");
            $this->cargarPedido(); // Recargar datos
            
            // Emitir evento para mostrar la impresión de la factura
            $this->dispatch('mostrarImpresionFactura', facturaId: $factura->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar pedido: ' . $e->getMessage());
        }
    }

    public function rechazarPedido()
    {
        try {
            $this->mostrarModalRechazar = false;
            
            $this->pedido->rechazar();
            session()->flash('success', 'Pedido rechazado');
            $this->cargarPedido();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al rechazar pedido: ' . $e->getMessage());
        }
    }

    // Métodos de paginación para productos
    public function nextPageProductos()
    {
        $this->paginaProductos++;
    }

    public function previousPageProductos()
    {
        if ($this->paginaProductos > 1) {
            $this->paginaProductos--;
        }
    }

    public function updatedBuscarProducto()
    {
        $this->paginaProductos = 1;
    }

    public function updatedFiltroNombreProducto()
    {
        $this->paginaProductos = 1;
    }

    public function getProductosPaginados()
    {
        if (!$this->pedido || !$this->pedido->items) {
            return [
                'datos' => collect([]),
                'total' => 0,
                'desde' => 0,
                'hasta' => 0,
                'paginaActual' => 1,
                'ultimaPagina' => 1
            ];
        }

        $items = $this->pedido->items;

        // Aplicar filtros
        if ($this->buscarProducto) {
            $busqueda = strtolower($this->buscarProducto);
            $items = $items->filter(function($item) use ($busqueda) {
                $nombreProducto = $item->producto ? strtolower($item->producto->nombre) : '';
                return str_contains($nombreProducto, $busqueda) || 
                       str_contains((string)$item->producto_id, $busqueda);
            });
        }

        if ($this->filtroNombreProducto) {
            $filtro = strtolower($this->filtroNombreProducto);
            $items = $items->filter(function($item) use ($filtro) {
                $nombreProducto = $item->producto ? strtolower($item->producto->nombre) : '';
                return str_contains($nombreProducto, $filtro);
            });
        }

        $total = $items->count();
        $desde = ($this->paginaProductos - 1) * $this->registrosPorPaginaProductos;
        $datos = $items->slice($desde, $this->registrosPorPaginaProductos)->values();
        
        return [
            'datos' => $datos,
            'total' => $total,
            'desde' => $desde + 1,
            'hasta' => min($desde + $this->registrosPorPaginaProductos, $total),
            'paginaActual' => $this->paginaProductos,
            'ultimaPagina' => ceil($total / $this->registrosPorPaginaProductos)
        ];
    }

    public function volverABandeja()
    {
        $this->dispatch('cambiarVista', 'BandejaPedidos');
    }

    public function render()
    {
        return view('livewire.detalle-pedido');
    }
}
