<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Bodega;
use App\Models\Segmento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Segmentos extends Component
{
    use WithPagination;

    public $bodegaId;
    public $bodega;

    // Filtros y búsqueda
    public $buscar = '';
    public $filtroEstado = '';
    public $registrosPorPagina = 10;

    // Propiedades para modales
    public $mostrarModalEliminar = false;
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';
    public $segmentoAEliminar = null;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'page' => ['except' => 1]
    ];

    public function mount($bodegaId)
    {
        $this->bodegaId = $bodegaId;
        $this->cargarBodega();
    }

    public function updatingBuscar()
    {
        $this->resetPage();
    }

    private function cargarBodega()
    {
        try {
            $this->bodega = Bodega::with('tienda')->findOrFail($this->bodegaId);
        } catch (\Exception $e) {
            Log::error('Error al cargar bodega para segmentos', [
                'bodega_id' => $this->bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar la bodega');
        }
    }

    public function render()
    {
        $segmentos = $this->obtenerSegmentos();

        return view('livewire.inventario.segmentos', [
            'segmentos' => $segmentos
        ]);
    }

    private function obtenerSegmentos()
    {
        try {
            $query = Segmento::where('bodega_id', $this->bodegaId)
                            ->with(['secciones']);

            // Aplicar filtro de búsqueda
            if (!empty($this->buscar)) {
                $query->where('descripcion', 'like', '%' . $this->buscar . '%');
            }

            // Aplicar filtro de estado
            if ($this->filtroEstado !== '') {
                $query->where('estado_id', $this->filtroEstado);
            }

            return $query->orderBy('descripcion')
                        ->paginate($this->registrosPorPagina);

        } catch (\Exception $e) {
            Log::error('Error al obtener segmentos', [
                'bodega_id' => $this->bodegaId,
                'mensaje' => $e->getMessage()
            ]);

            return collect()->paginate($this->registrosPorPagina);
        }
    }

    // ===== MÉTODOS DE NAVEGACIÓN =====

    public function crearNuevoSegmento()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.SegmentoForm', parametros: [
            'bodegaId' => $this->bodegaId
        ]);
    }

    public function editarSegmento($segmentoId)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.SegmentoForm', parametros: [
            'bodegaId' => $this->bodegaId,
            'segmentoId' => $segmentoId
        ]);
    }

    public function irAFormulario($segmentoId = null)
    {
        if ($segmentoId) {
            $this->dispatch('cambiarVista', ruta: 'Inventario.SegmentoForm', parametros: [
                'bodegaId' => $this->bodegaId,
                'segmentoId' => $segmentoId
            ]);
        } else {
            $this->dispatch('cambiarVista', ruta: 'Inventario.SegmentoForm', parametros: [
                'bodegaId' => $this->bodegaId
            ]);
        }
    }

    public function verSecciones($segmentoId)
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Secciones', parametros: [
            'bodegaId' => $this->bodegaId,
            'segmentoId' => $segmentoId
        ]);
    }

    public function volverABodegas()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Bodegas');
    }

    // ===== MÉTODOS DE FILTRADO =====

    public function limpiarFiltros()
    {
        $this->buscar = '';
        $this->filtroEstado = '';
        $this->resetPage();
    }

    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatedRegistrosPorPagina()
    {
        $this->resetPage();
    }

    // ===== MÉTODOS DE ELIMINACIÓN =====

    public function eliminarSegmento($segmentoId)
    {
        $this->confirmarEliminar($segmentoId);
    }

    public function confirmarEliminar($segmentoId)
    {
        try {
            $segmento = Segmento::findOrFail($segmentoId);
            $this->segmentoAEliminar = $segmento;
            $this->mostrarModalEliminar = true;
        } catch (\Exception $e) {
            Log::error('Error al cargar segmento para eliminar', [
                'segmento_id' => $segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar el segmento');
        }
    }

    public function eliminar()
    {
        if (!$this->segmentoAEliminar) {
            $this->mostrarError('No se ha seleccionado ningún segmento para eliminar');
            return;
        }

        try {
            Segmento::eliminarSegmento($this->segmentoAEliminar->id);

            Log::info('Segmento eliminado exitosamente', [
                'segmento_id' => $this->segmentoAEliminar->id,
                'usuario_id' => Auth::id()
            ]);

            $this->mostrarModalEliminar = false;
            $this->segmentoAEliminar = null;
            $this->mostrarExito('Segmento eliminado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al eliminar segmento', [
                'segmento_id' => $this->segmentoAEliminar->id,
                'mensaje' => $e->getMessage(),
                'usuario_id' => Auth::id()
            ]);

            $this->mostrarModalEliminar = false;
            $this->segmentoAEliminar = null;
            $this->mostrarError('Error al eliminar el segmento: ' . $e->getMessage());
        }
    }

    public function cancelarEliminar()
    {
        $this->mostrarModalEliminar = false;
        $this->segmentoAEliminar = null;
    }

    // ===== MÉTODOS DE MODALES =====

    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    public function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
    }

    // ===== MÉTODOS DE UTILIDAD =====

    public function obtenerEstadoTexto($estado)
    {
        return $estado == 1 ? 'Activo' : 'Inactivo';
    }

    public function obtenerEstadoClase($estado)
    {
        return $estado == 1 ? 'badge bg-success' : 'badge bg-secondary';
    }
}
