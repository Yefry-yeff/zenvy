<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PedidoWeb;

class DetallePedido extends Component
{
    public $pedidoId;
    public $pedido;

    public function mount($pedidoId = null)
    {
        if (!$pedidoId) {
            session()->flash('error', 'ID de pedido no proporcionado');
            $this->dispatch('cambiarVista', ['BandejaPedidos']);
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
            $this->dispatch('cambiarVista', ['BandejaPedidos']);
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
            $orderService = app(\App\Services\Api\OrderService::class);
            $factura = $orderService->convertirPedidoAFactura(
                $this->pedidoId,
                auth()->id()
            );

            session()->flash('success', "Pedido procesado exitosamente. Factura ID: {$factura->id}");
            $this->cargarPedido(); // Recargar datos
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar pedido: ' . $e->getMessage());
        }
    }

    public function rechazarPedido()
    {
        try {
            $this->pedido->rechazar();
            session()->flash('success', 'Pedido rechazado');
            $this->cargarPedido();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al rechazar pedido: ' . $e->getMessage());
        }
    }

    public function volverABandeja()
    {
        $this->dispatch('cambiarVista', ['BandejaPedidos']);
    }

    public function render()
    {
        return view('livewire.detalle-pedido');
    }
}
