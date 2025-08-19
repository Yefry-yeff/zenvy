<?php

namespace App\Livewire\Catalogo;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class Servicios extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $filtroEstado = '';

    // Propiedades para mensajes
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $tipoAlerta = 'success'; // success, danger, warning, info

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Inicialización si es necesaria
    }

    public function render()
    {
        $servicios = Servicio::with(['estado', 'isv'])
            ->when($this->busqueda, function ($query) {
                $query->where('nombre', 'like', '%' . $this->busqueda . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->busqueda . '%');
            })
            ->when($this->filtroEstado, function ($query) {
                $query->where('estado_id', $this->filtroEstado);
            })
            ->orderBy('nombre')
            ->paginate(12); // 12 servicios por página para una buena visualización en grid

        return view('livewire.catalogo.servicios', [
            'servicios' => $servicios
        ]);
    }

    public function editarServicio($id)
    {
        // Navegar al formulario de edición
        $this->dispatch('cambiarVista', ruta: 'Catalogo.ServicioForm', parametros: ['id' => $id]);
    }

    public function crearServicio()
    {
        // Navegar al formulario de creación
        $this->dispatch('cambiarVista', ruta: 'Catalogo.ServicioForm');
    }

    public function updatedBusqueda()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->filtroEstado = '';
        $this->resetPage();
    }
}
