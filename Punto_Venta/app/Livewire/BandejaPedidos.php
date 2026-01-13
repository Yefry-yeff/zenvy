<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PedidoWeb;

class BandejaPedidos extends Component
{
    use WithPagination;

    public function render()
    {
        $pedidos = PedidoWeb::with('items')
            ->orderBy('leido', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.bandeja-pedidos', [
            'pedidos' => $pedidos
        ]);
    }

    public function verDetalle($pedidoId)
    {
        // Redirigir a la vista de detalle (usando navegación web tradicional)
        return redirect()->route('pedidos-web.show', $pedidoId);
    }
}
