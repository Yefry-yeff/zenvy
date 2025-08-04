<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Bodega;
use App\Models\Segmento;
use App\Models\Seccion;
use App\Models\Tiendas;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Bodegas extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $filtroTienda = '';
    public $filtroEstado = '';

    // Propiedades para modal de eliminación
    public $mostrarModalEliminar = false;
    public $bodegaAEliminar = null;

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

    // ===== MÉTODOS DE ELIMINACIÓN =====

    public function eliminarBodega($bodegaId)
    {
        try {
            $this->bodegaAEliminar = Bodega::findOrFail($bodegaId);
            $this->mostrarModalEliminar = true;
        } catch (\Exception $e) {
            Log::error('Error al cargar bodega para eliminar', [
                'bodega_id' => $bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al cargar la bodega para eliminar.');
        }
    }

    public function cancelarEliminar()
    {
        $this->mostrarModalEliminar = false;
        $this->bodegaAEliminar = null;
    }

    public function confirmarEliminacion()
    {
        if (!$this->bodegaAEliminar) {
            return;
        }

        try {
            DB::beginTransaction();

            $bodegaId = $this->bodegaAEliminar->id;
            $bodegaNombre = $this->bodegaAEliminar->nombre;

            // 1. Primero eliminar todas las secciones de todos los segmentos de esta bodega
            $segmentos = Segmento::where('bodega_id', $bodegaId)->get();
            $totalSecciones = 0;
            $totalSegmentos = $segmentos->count();

            foreach ($segmentos as $segmento) {
                $seccionesCount = $segmento->secciones()->count();
                $totalSecciones += $seccionesCount;
                
                // Eliminar secciones del segmento (soft delete)
                $segmento->secciones()->update(['estado_id' => 0]);
            }

            // 2. Luego eliminar todos los segmentos de esta bodega
            Segmento::where('bodega_id', $bodegaId)->update(['estado_id' => 0]);

            // 3. Finalmente eliminar la bodega
            Bodega::where('id', $bodegaId)->update(['estado_id' => 0]);

            DB::commit();

            Log::info('Bodega eliminada exitosamente desde lista con eliminación en cascada', [
                'bodega_id' => $bodegaId,
                'bodega_nombre' => $bodegaNombre,
                'segmentos_eliminados' => $totalSegmentos,
                'secciones_eliminadas' => $totalSecciones,
                'usuario_id' => Auth::id()
            ]);

            // Cerrar modal
            $this->mostrarModalEliminar = false;
            $this->bodegaAEliminar = null;

            // Refrescar la página para mostrar los cambios
            $this->resetPage();

            session()->flash('message', "Bodega '{$bodegaNombre}' eliminada exitosamente. Se eliminaron {$totalSegmentos} segmento(s) y {$totalSecciones} sección(es) asociadas.");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error al eliminar bodega desde lista con cascada', [
                'bodega_id' => $this->bodegaAEliminar->id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'usuario_id' => Auth::id()
            ]);

            $this->mostrarModalEliminar = false;
            $this->bodegaAEliminar = null;

            session()->flash('error', 'Error al eliminar la bodega: ' . $e->getMessage());
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
