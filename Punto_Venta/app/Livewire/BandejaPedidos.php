<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PedidoWeb;

class BandejaPedidos extends Component
{
    // Propiedades para filtros y búsqueda
    public $buscar = '';
    public $filtroEstado = '';
    public $filtroCliente = '';
    public $filtroFecha = '';
    public $ordenarPor = 'created_at';
    public $direccionOrden = 'desc';
    
    // Paginación manual
    public $registrosPorPagina = 10;
    public $pagina = 1;

    public function mount()
    {
        // Inicialización si es necesaria
    }

    // Métodos para actualizar filtros (resetean página)
    public function updatedBuscar()
    {
        $this->pagina = 1;
    }

    public function updatedFiltroEstado()
    {
        $this->pagina = 1;
    }

    public function updatedFiltroCliente()
    {
        $this->pagina = 1;
    }

    public function updatedFiltroFecha()
    {
        $this->pagina = 1;
    }

    public function updatedRegistrosPorPagina()
    {
        $this->pagina = 1;
    }

    // Navegación de páginas
    public function siguientePagina()
    {
        $this->pagina++;
    }

    public function anteriorPagina()
    {
        if ($this->pagina > 1) {
            $this->pagina--;
        }
    }

    public function irAPagina($pagina)
    {
        $this->pagina = $pagina;
    }

    // Ordenamiento
    public function ordenar($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccionOrden = 'asc';
        }
        $this->pagina = 1;
    }

    public function render()
    {
        $query = PedidoWeb::with('items.producto');

        // Aplicar filtros
        if ($this->buscar) {
            $busqueda = '%' . $this->buscar . '%';
            $query->where(function($q) use ($busqueda) {
                $q->where('numero_pedido', 'like', $busqueda)
                  ->orWhere('cliente_nombre', 'like', $busqueda)
                  ->orWhere('cliente_email', 'like', $busqueda);
            });
        }

        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        if ($this->filtroCliente) {
            $query->where('cliente_nombre', 'like', '%' . $this->filtroCliente . '%');
        }

        if ($this->filtroFecha) {
            $query->whereDate('created_at', $this->filtroFecha);
        }

        // Ordenamiento especial: no leídos primero, luego por el campo seleccionado
        $query->orderBy('leido', 'asc')
              ->orderBy($this->ordenarPor, $this->direccionOrden);

        // Obtener total
        $total = $query->count();

        // Paginación
        $desde = ($this->pagina - 1) * $this->registrosPorPagina;
        $pedidos = $query->skip($desde)
                        ->take($this->registrosPorPagina)
                        ->get();

        $ultimaPagina = ceil($total / $this->registrosPorPagina);

        return view('livewire.bandeja-pedidos', [
            'pedidos' => $pedidos,
            'total' => $total,
            'desde' => $desde + 1,
            'hasta' => min($desde + $this->registrosPorPagina, $total),
            'paginaActual' => $this->pagina,
            'ultimaPagina' => $ultimaPagina
        ]);
    }

    public function verDetalle($pedidoId)
    {
        // Redirigir a la vista de detalle
        return redirect()->route('pedidos-web.show', $pedidoId);
    }
}
