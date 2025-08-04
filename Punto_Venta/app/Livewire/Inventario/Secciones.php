<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Secciones extends Component
{
    use WithPagination;

    public $bodegaId;
    public $segmentoId;
    public $bodega;
    public $segmento;
    
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
    public $seccionAEliminar = null;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'page' => ['except' => 1]
    ];

    public function mount($bodegaId, $segmentoId)
    {
        $this->bodegaId = $bodegaId;
        $this->segmentoId = $segmentoId;
        $this->cargarDatos();
    }

    private function cargarDatos()
    {
        try {
            $this->bodega = Bodega::with('tienda')->findOrFail($this->bodegaId);
            $this->segmento = Segmento::findOrFail($this->segmentoId);
        } catch (\Exception $e) {
            Log::error('Error al cargar datos para secciones', [
                'bodega_id' => $this->bodegaId,
                'segmento_id' => $this->segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos');
        }
    }

    public function render()
    {
        $secciones = $this->obtenerSecciones();
        
        return view('livewire.inventario.secciones', [
            'secciones' => $secciones
        ]);
    }

    private function obtenerSecciones()
    {
        try {
            $query = Seccion::where('segmento_id', $this->segmentoId);

            // Aplicar filtro de búsqueda
            if (!empty($this->buscar)) {
                $query->where(function($q) {
                    $q->where('descripcion', 'like', '%' . $this->buscar . '%')
                      ->orWhere('numeracion', 'like', '%' . $this->buscar . '%');
                });
            }

            // Aplicar filtro de estado
            if ($this->filtroEstado !== '') {
                $query->where('estado_id', $this->filtroEstado);
            }

            return $query->orderBy('numeracion')
                        ->orderBy('descripcion')
                        ->paginate($this->registrosPorPagina);

        } catch (\Exception $e) {
            Log::error('Error al obtener secciones', [
                'segmento_id' => $this->segmentoId,
                'mensaje' => $e->getMessage()
            ]);
            
            return collect()->paginate($this->registrosPorPagina);
        }
    }

    // ===== MÉTODOS DE NAVEGACIÓN =====

    public function irAFormulario($seccionId = null)
    {
        if ($seccionId) {
            $this->dispatch('cambiarVista', ruta: 'Inventario.SeccionForm', parametros: [
                'bodegaId' => $this->bodegaId,
                'segmentoId' => $this->segmentoId,
                'seccionId' => $seccionId
            ]);
        } else {
            $this->dispatch('cambiarVista', ruta: 'Inventario.SeccionForm', parametros: [
                'bodegaId' => $this->bodegaId,
                'segmentoId' => $this->segmentoId
            ]);
        }
    }

    public function volverASegmentos()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Segmentos', parametros: [
            'bodegaId' => $this->bodegaId
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

    public function confirmarEliminar($seccionId)
    {
        try {
            $seccion = Seccion::findOrFail($seccionId);
            $this->seccionAEliminar = $seccion;
            $this->mostrarModalEliminar = true;
        } catch (\Exception $e) {
            Log::error('Error al cargar sección para eliminar', [
                'seccion_id' => $seccionId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar la sección');
        }
    }

    public function eliminar()
    {
        if (!$this->seccionAEliminar) {
            $this->mostrarError('No se ha seleccionado ninguna sección para eliminar');
            return;
        }

        try {
            Seccion::eliminarSeccion($this->seccionAEliminar->id);
            
            Log::info('Sección eliminada exitosamente', [
                'seccion_id' => $this->seccionAEliminar->id,
                'usuario_id' => Auth::id()
            ]);

            $this->mostrarModalEliminar = false;
            $this->seccionAEliminar = null;
            $this->mostrarExito('Sección eliminada exitosamente');
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar sección', [
                'seccion_id' => $this->seccionAEliminar->id,
                'mensaje' => $e->getMessage(),
                'usuario_id' => Auth::id()
            ]);
            
            $this->mostrarModalEliminar = false;
            $this->seccionAEliminar = null;
            $this->mostrarError('Error al eliminar la sección: ' . $e->getMessage());
        }
    }

    public function cancelarEliminar()
    {
        $this->mostrarModalEliminar = false;
        $this->seccionAEliminar = null;
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

    public function editarSeccion($id)
    {
        // Aquí puedes redirigir a la vista de edición, abrir un modal, o asignar la sección a una propiedad para edición
        // Ejemplo: $this->seccionAEditar = Seccion::findOrFail($id);
        // Por ahora solo lo dejamos como placeholder para evitar el error
    }
}
