<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CambioUnidadesExport;
use Carbon\Carbon;

class CambiosDeUnidades extends Component
{
    use WithPagination;

    // Filtros
    public $filtroProducto = '';
    public $filtroUsuario = '';
    public $filtroFechaInicio = '';
    public $filtroFechaFin = '';
    public $filtroBodega = '';
    public $filtroUnidadOriginal = '';
    public $filtroUnidadNueva = '';

    // Ordenamiento
    public $ordenarPor = 'created_at';
    public $direccionOrden = 'desc';

    // Listas para filtros
    public $usuarios = [];
    public $bodegas = [];
    public $unidadesMedida = [];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Cargar usuarios que han realizado cambios
        $this->usuarios = DB::table('cambio_unidad')
            ->join('users', 'cambio_unidad.users_id', '=', 'users.id')
            ->select('users.id as usuario_id', 'users.name as usuario_nombre')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        // Cargar bodegas que tienen cambios
        $this->bodegas = DB::table('cambio_unidad')
            ->join('bodega', 'cambio_unidad.bodega_id', '=', 'bodega.id')
            ->select('bodega.id', 'bodega.nombre')
            ->groupBy('bodega.id', 'bodega.nombre')
            ->orderBy('bodega.nombre')
            ->get();

        // Cargar unidades de medida
        $this->unidadesMedida = DB::table('unidad_medida')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Establecer fechas por defecto (último mes)
        if (empty($this->filtroFechaInicio)) {
            $this->filtroFechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        }
        if (empty($this->filtroFechaFin)) {
            $this->filtroFechaFin = Carbon::now()->format('Y-m-d');
        }
    }

    public function ordenar($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccionOrden = 'asc';
        }
        $this->resetPage();
    }

    public function updatedFiltroProducto()
    {
        $this->resetPage();
    }

    public function updatedFiltroUsuario()
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaInicio()
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaFin()
    {
        $this->resetPage();
    }

    public function updatedFiltroBodega()
    {
        $this->resetPage();
    }

    public function updatedFiltroUnidadOriginal()
    {
        $this->resetPage();
    }

    public function updatedFiltroUnidadNueva()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'filtroProducto',
            'filtroUsuario',
            'filtroFechaInicio',
            'filtroFechaFin',
            'filtroBodega',
            'filtroUnidadOriginal',
            'filtroUnidadNueva'
        ]);
        
        // Restablecer fechas por defecto
        $this->filtroFechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        $this->filtroFechaFin = Carbon::now()->format('Y-m-d');
        
        $this->resetPage();
    }

    public function descargarExcel()
    {
        $cambios = $this->obtenerCambios(false);

        return Excel::download(
            new CambioUnidadesExport($cambios), 
            'cambios-unidades-' . date('Y-m-d-His') . '.xlsx'
        );
    }

    private function obtenerCambios($paginar = true)
    {
        $query = DB::table('cambio_unidad as cu')
            ->join('users', 'cu.users_id', '=', 'users.id')
            ->join('producto as p', 'cu.producto_id', '=', 'p.id')
            ->join('bodega as b', 'cu.bodega_id', '=', 'b.id')
            ->join('seccion as s', 'cu.seccion_id', '=', 's.id')
            ->join('unidad_medida as um_original', 'cu.unidad_medida_id_original', '=', 'um_original.id')
            ->join('unidad_medida as um_nueva', 'cu.unidad_medida_id_nueva', '=', 'um_nueva.id')
            ->select(
                'cu.id',
                'cu.created_at as fecha_cambio',
                'cu.cantidad_rebajada',
                'cu.cantidad_convertida',
                'cu.factor_conversion',
                'cu.motivo',
                'users.name as usuario_nombre',
                'p.nombre as producto_nombre',
                'p.id as producto_id',
                'b.nombre as bodega_nombre',
                's.descripcion as seccion_nombre',
                'um_original.nombre as unidad_original',
                'um_nueva.nombre as unidad_nueva'
            );

        // Aplicar filtros
        if (!empty($this->filtroProducto)) {
            $query->where(function($q) {
                $q->where('p.nombre', 'like', '%' . $this->filtroProducto . '%')
                  ->orWhere('p.id', 'like', '%' . $this->filtroProducto . '%');
            });
        }

        if (!empty($this->filtroUsuario)) {
            $query->where('cu.users_id', $this->filtroUsuario);
        }

        if (!empty($this->filtroBodega)) {
            $query->where('b.nombre', 'like', '%' . $this->filtroBodega . '%');
        }

        if (!empty($this->filtroUnidadOriginal)) {
            $query->where('cu.unidad_medida_id_original', $this->filtroUnidadOriginal);
        }

        if (!empty($this->filtroUnidadNueva)) {
            $query->where('cu.unidad_medida_id_nueva', $this->filtroUnidadNueva);
        }

        if (!empty($this->filtroFechaInicio)) {
            $query->whereDate('cu.created_at', '>=', $this->filtroFechaInicio);
        }

        if (!empty($this->filtroFechaFin)) {
            $query->whereDate('cu.created_at', '<=', $this->filtroFechaFin);
        }

        // Ordenamiento
        if ($this->ordenarPor === 'usuario_nombre') {
            $query->orderBy('users.name', $this->direccionOrden);
        } else {
            $query->orderBy('cu.' . $this->ordenarPor, $this->direccionOrden);
        }

        // Retornar con o sin paginación
        if ($paginar) {
            return $query->paginate(25);
        } else {
            return $query->get();
        }
    }

    public function render()
    {
        $cambios = $this->obtenerCambios();

        return view('livewire.reporte.cambios-de-unidades', [
            'cambios' => $cambios
        ]);
    }
}
