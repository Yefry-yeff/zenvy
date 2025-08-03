<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Bodega;
use App\Models\Tiendas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Bodegas extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $filtroTienda = '';
    public $filtroEstado = '';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Inicialización si es necesaria
    }

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function updatingFiltroTienda()
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado()
    {
        $this->resetPage();
    }

    public function crearNuevaBodega()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.BodegaForm');
    }

    public function editarBodega($bodegaId)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.BodegaForm', parametros: ['bodegaId' => $bodegaId]);
    }

    public function verSegmentos($bodegaId)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Segmentos', parametros: ['bodegaId' => $bodegaId]);
    }

    public function eliminarBodega($bodegaId)
    {
        try {
            Bodega::eliminarBodega($bodegaId);

            session()->flash('success', 'Bodega eliminada exitosamente.');

            Log::info('Bodega eliminada', [
                'bodega_id' => $bodegaId,
                'usuario' => Auth::id()
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar la bodega.');

            Log::error('Error al eliminar bodega', [
                'bodega_id' => $bodegaId,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);
        }
    }

    public function render()
    {
        $query = Bodega::with(['tienda', 'segmentos.secciones']);

        // Aplicar filtros
        if ($this->busqueda) {
            $query->where('nombre', 'like', '%' . $this->busqueda . '%');
        }

        if ($this->filtroTienda) {
            $query->where('tienda_id', $this->filtroTienda);
        }

        if ($this->filtroEstado !== '') {
            $query->where('estado_id', $this->filtroEstado);
        }

        $bodegas = $query->orderBy('nombre')->paginate(9);
        $tiendas = Tiendas::where('estado_id', 1)->orderBy('denominacion_social')->get();

        return view('livewire.inventario.bodegas', compact('bodegas', 'tiendas'));
    }
}
