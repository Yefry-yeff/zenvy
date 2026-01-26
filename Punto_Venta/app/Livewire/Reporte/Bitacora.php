<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Bitacora as BitacoraModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Bitacora extends Component
{
    use WithPagination;

    // Filtros
    public $filtroUsuario = '';
    public $filtroModulo = '';
    public $filtroAccion = '';
    public $filtroDescripcion = '';
    public $filtroFechaInicio = '';
    public $filtroFechaFin = '';
    public $filtroIp = '';

    // Ordenamiento
    public $ordenarPor = 'created_at';
    public $direccionOrden = 'desc';

    // Paginación
    public $porPagina = 20;

    protected $paginationTheme = 'bootstrap';

    // Resetear paginación al cambiar filtros
    public function updatedFiltroUsuario() { $this->resetPage(); }
    public function updatedFiltroModulo() { $this->resetPage(); }
    public function updatedFiltroAccion() { $this->resetPage(); }
    public function updatedFiltroDescripcion() { $this->resetPage(); }
    public function updatedFiltroFechaInicio() { $this->resetPage(); }
    public function updatedFiltroFechaFin() { $this->resetPage(); }
    public function updatedFiltroIp() { $this->resetPage(); }

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

    public function limpiarFiltros()
    {
        $this->filtroUsuario = '';
        $this->filtroModulo = '';
        $this->filtroAccion = '';
        $this->filtroDescripcion = '';
        $this->filtroFechaInicio = '';
        $this->filtroFechaFin = '';
        $this->filtroIp = '';
        $this->resetPage();
    }

    public function obtenerRegistros()
    {
        $query = BitacoraModel::query()
            ->with('usuario')
            ->select('bitacora.*');

        // Aplicar filtros
        if (!empty($this->filtroUsuario)) {
            $query->whereHas('usuario', function($q) {
                $q->where('name', 'like', '%' . $this->filtroUsuario . '%');
            });
        }

        if (!empty($this->filtroModulo)) {
            $query->where('modulo', 'like', '%' . $this->filtroModulo . '%');
        }

        if (!empty($this->filtroAccion)) {
            $query->where('accion', 'like', '%' . $this->filtroAccion . '%');
        }

        if (!empty($this->filtroDescripcion)) {
            $query->where('descripcion', 'like', '%' . $this->filtroDescripcion . '%');
        }

        if (!empty($this->filtroFechaInicio)) {
            $query->whereDate('created_at', '>=', $this->filtroFechaInicio);
        }

        if (!empty($this->filtroFechaFin)) {
            $query->whereDate('created_at', '<=', $this->filtroFechaFin);
        }

        if (!empty($this->filtroIp)) {
            $query->where('ip_Equipo', 'like', '%' . $this->filtroIp . '%');
        }

        // Ordenamiento
        $query->orderBy($this->ordenarPor, $this->direccionOrden);

        return $query->paginate($this->porPagina);
    }

    public function obtenerEstadisticas()
    {
        $query = BitacoraModel::query();

        // Aplicar los mismos filtros
        if (!empty($this->filtroUsuario)) {
            $query->whereHas('usuario', function($q) {
                $q->where('name', 'like', '%' . $this->filtroUsuario . '%');
            });
        }

        if (!empty($this->filtroModulo)) {
            $query->where('modulo', 'like', '%' . $this->filtroModulo . '%');
        }

        if (!empty($this->filtroAccion)) {
            $query->where('accion', 'like', '%' . $this->filtroAccion . '%');
        }

        if (!empty($this->filtroDescripcion)) {
            $query->where('descripcion', 'like', '%' . $this->filtroDescripcion . '%');
        }

        if (!empty($this->filtroFechaInicio)) {
            $query->whereDate('created_at', '>=', $this->filtroFechaInicio);
        }

        if (!empty($this->filtroFechaFin)) {
            $query->whereDate('created_at', '<=', $this->filtroFechaFin);
        }

        if (!empty($this->filtroIp)) {
            $query->where('ip_Equipo', 'like', '%' . $this->filtroIp . '%');
        }

        return [
            'total' => $query->count(),
            'porModulo' => $query->select('modulo', DB::raw('count(*) as total'))
                ->groupBy('modulo')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get(),
            'porAccion' => $query->select('accion', DB::raw('count(*) as total'))
                ->groupBy('accion')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    public function render()
    {
        $registros = $this->obtenerRegistros();
        $estadisticas = $this->obtenerEstadisticas();

        return view('livewire.reporte.bitacora', [
            'registros' => $registros,
            'estadisticas' => $estadisticas
        ]);
    }
}
