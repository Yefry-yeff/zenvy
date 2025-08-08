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
    public $filtroTipo = '';

    // Propiedades para modal de inactivación
    public $mostrarModalInactivar = false;
    public $bodegaAInactivar = null;

    // Propiedades para modal de activación
    public $mostrarModalActivar = false;
    public $bodegaAActivar = null;

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

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->busqueda = '';
        $this->filtroTienda = '';
        $this->filtroEstado = '';
        $this->filtroTipo = '';
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

    // ===== MÉTODOS DE INACTIVACIÓN =====

    public function inactivarBodega($bodegaId)
    {
        try {
            $this->bodegaAInactivar = Bodega::findOrFail($bodegaId);
            $this->mostrarModalInactivar = true;
        } catch (\Exception $e) {
            Log::error('Error al cargar bodega para inactivar', [
                'bodega_id' => $bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al cargar la bodega para inactivar.');
        }
    }

    public function cancelarInactivar()
    {
        $this->mostrarModalInactivar = false;
        $this->bodegaAInactivar = null;
    }

    public function confirmarInactivacion()
    {
        if (!$this->bodegaAInactivar) {
            return;
        }

        try {
            DB::beginTransaction();

            $bodegaId = $this->bodegaAInactivar->id;
            $bodegaNombre = $this->bodegaAInactivar->nombre;

            // 1. Primero inactivar todas las secciones de todos los segmentos de esta bodega
            $segmentos = Segmento::where('bodega_id', $bodegaId)->get();
            $totalSecciones = 0;
            $totalSegmentos = $segmentos->count();

            foreach ($segmentos as $segmento) {
                $seccionesCount = $segmento->secciones()->count();
                $totalSecciones += $seccionesCount;
                
                // Inactivar secciones del segmento (soft delete usando estado inactivo)
                $segmento->secciones()->update(['estado_id' => 2]);
            }

            // 2. No inactivamos segmentos ya que no tienen estado_id - los dejamos como están
            // Los segmentos seguirán existiendo pero sus secciones estarán inactivas

            // 3. Finalmente inactivar la bodega (soft delete usando estado inactivo)
            Bodega::where('id', $bodegaId)->update(['estado_id' => 2]);

            DB::commit();

            Log::info('Bodega inactivada exitosamente desde lista con inactivación en cascada', [
                'bodega_id' => $bodegaId,
                'bodega_nombre' => $bodegaNombre,
                'segmentos_afectados' => $totalSegmentos,
                'secciones_inactivadas' => $totalSecciones,
                'usuario_id' => Auth::id()
            ]);

            // Cerrar modal
            $this->mostrarModalInactivar = false;
            $this->bodegaAInactivar = null;

            // Refrescar la página para mostrar los cambios
            $this->resetPage();

            session()->flash('message', "Bodega '{$bodegaNombre}' inactivada exitosamente. Se inactivaron {$totalSecciones} sección(es) asociadas.");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error al inactivar bodega desde lista', [
                'bodega_id' => $this->bodegaAInactivar->id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'usuario_id' => Auth::id()
            ]);

            $this->mostrarModalInactivar = false;
            $this->bodegaAInactivar = null;

            session()->flash('error', 'Error al inactivar la bodega: ' . $e->getMessage());
        }
    }

    // ===== MÉTODOS DE ACTIVACIÓN =====

    public function activarBodega($bodegaId)
    {
        try {
            $this->bodegaAActivar = Bodega::findOrFail($bodegaId);
            $this->mostrarModalActivar = true;
        } catch (\Exception $e) {
            Log::error('Error al cargar bodega para activar', [
                'bodega_id' => $bodegaId,
                'mensaje' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al cargar la bodega para activar.');
        }
    }

    public function cancelarActivar()
    {
        $this->mostrarModalActivar = false;
        $this->bodegaAActivar = null;
    }

    public function confirmarActivacion()
    {
        if (!$this->bodegaAActivar) {
            return;
        }

        try {
            DB::beginTransaction();

            $bodegaId = $this->bodegaAActivar->id;
            $bodegaNombre = $this->bodegaAActivar->nombre;

            // 1. Primero activar todas las secciones de todos los segmentos de esta bodega
            $segmentos = Segmento::where('bodega_id', $bodegaId)->get();
            $totalSecciones = 0;
            $totalSegmentos = $segmentos->count();

            foreach ($segmentos as $segmento) {
                $seccionesCount = $segmento->secciones()->count();
                $totalSecciones += $seccionesCount;
                
                // Activar secciones del segmento (estado activo)
                $segmento->secciones()->update(['estado_id' => 1]);
            }

            // 2. Activar la bodega (estado activo)
            Bodega::where('id', $bodegaId)->update(['estado_id' => 1]);

            DB::commit();

            Log::info('Bodega activada exitosamente desde lista con activación en cascada', [
                'bodega_id' => $bodegaId,
                'bodega_nombre' => $bodegaNombre,
                'segmentos_afectados' => $totalSegmentos,
                'secciones_activadas' => $totalSecciones,
                'usuario_id' => Auth::id()
            ]);

            // Cerrar modal
            $this->mostrarModalActivar = false;
            $this->bodegaAActivar = null;

            // Refrescar la página para mostrar los cambios
            $this->resetPage();

            session()->flash('success', "Bodega '{$bodegaNombre}' activada exitosamente. Se activaron {$totalSecciones} sección(es) asociadas.");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error al activar bodega desde lista', [
                'bodega_id' => $this->bodegaAActivar->id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'usuario_id' => Auth::id()
            ]);

            $this->mostrarModalActivar = false;
            $this->bodegaAActivar = null;

            session()->flash('error', 'Error al activar la bodega: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = Bodega::with(['tienda', 'direccion', 'segmentos.secciones']);

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

        if ($this->filtroTipo !== '') {
            $query->where('principal', $this->filtroTipo);
        }

        $bodegas = $query->orderBy('nombre')->paginate(9);
        
        $tiendas = Tiendas::where('estado_id', 1)->orderBy('denominacion_social')->get();

        return view('livewire.inventario.bodegas', compact('bodegas', 'tiendas'));
    }
}
