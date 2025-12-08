<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AjusteUnidadesExport;
use Carbon\Carbon;

class AjusteDeUnidades extends Component
{
    use WithPagination;

    // Filtros
    public $filtroProducto = '';
    public $filtroUsuario = '';
    public $filtroTipoAjuste = ''; // 'aumentar', 'disminuir', ''
    public $filtroFechaInicio = '';
    public $filtroFechaFin = '';
    public $filtroBodega = '';

    // Ordenamiento
    public $ordenarPor = 'created_at';
    public $direccionOrden = 'desc';

    // Listas para filtros
    public $usuarios = [];
    public $bodegas = [];

    protected $queryString = [
        'filtroProducto' => ['except' => ''],
        'filtroUsuario' => ['except' => ''],
        'filtroTipoAjuste' => ['except' => ''],
        'filtroFechaInicio' => ['except' => ''],
        'filtroFechaFin' => ['except' => ''],
        'filtroBodega' => ['except' => ''],
        'ordenarPor' => ['except' => 'created_at'],
        'direccionOrden' => ['except' => 'desc'],
    ];

    public function mount()
    {
        // Cargar usuarios que han realizado ajustes
        $this->usuarios = DB::table('ajuste_inventario')
            ->join('users', 'ajuste_inventario.users_id', '=', 'users.id')
            ->select('users.id as usuario_id', 'users.name as usuario_nombre')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        // Cargar bodegas que tienen ajustes
        $this->bodegas = DB::table('ajuste_inventario')
            ->join('bodega', 'ajuste_inventario.bodega_id', '=', 'bodega.id')
            ->select('bodega.id', 'bodega.nombre')
            ->groupBy('bodega.id', 'bodega.nombre')
            ->orderBy('bodega.nombre')
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

    public function updatedFiltroTipoAjuste()
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

    public function limpiarFiltros()
    {
        $this->reset([
            'filtroProducto',
            'filtroUsuario',
            'filtroTipoAjuste',
            'filtroFechaInicio',
            'filtroFechaFin',
            'filtroBodega'
        ]);
        
        // Restablecer fechas por defecto
        $this->filtroFechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        $this->filtroFechaFin = Carbon::now()->format('Y-m-d');
        
        $this->resetPage();
    }

    public function descargarExcel()
    {
        $ajustes = $this->obtenerAjustes(false);

        return Excel::download(
            new AjusteUnidadesExport($ajustes), 
            'ajuste-unidades-' . date('Y-m-d-His') . '.xlsx'
        );
    }

    private function obtenerAjustes($paginar = true)
    {
        $query = DB::table('ajuste_inventario as ai')
            ->join('users', 'ai.users_id', '=', 'users.id')
            ->join('producto as p', 'ai.producto_id', '=', 'p.id')
            ->join('bodega as b', 'ai.bodega_id', '=', 'b.id')
            ->join('seccion as s', 'ai.seccion_id', '=', 's.id')
            ->join('unidad_medida as um', 'ai.unidad_medida_id', '=', 'um.id')
            ->select(
                'ai.id',
                'ai.created_at as fecha_ajuste',
                'ai.tipo_ajuste',
                'ai.cantidad_anterior',
                'ai.cantidad_ajustada',
                'ai.cantidad_nueva',
                'ai.motivo',
                'users.name as usuario_nombre',
                'p.nombre as producto_nombre',
                'p.id as producto_id',
                'b.nombre as bodega_nombre',
                's.descripcion as seccion_nombre',
                'um.nombre as unidad_medida'
            );

        // Aplicar filtros
        if (!empty($this->filtroProducto)) {
            $query->where(function($q) {
                $q->where('p.nombre', 'like', '%' . $this->filtroProducto . '%')
                  ->orWhere('p.id', 'like', '%' . $this->filtroProducto . '%');
            });
        }

        if (!empty($this->filtroUsuario)) {
            $query->where('ai.users_id', $this->filtroUsuario);
        }

        if (!empty($this->filtroTipoAjuste)) {
            $query->where('ai.tipo_ajuste', $this->filtroTipoAjuste);
        }

        if (!empty($this->filtroBodega)) {
            $query->where('b.nombre', 'like', '%' . $this->filtroBodega . '%');
        }

        if (!empty($this->filtroFechaInicio)) {
            $query->whereDate('ai.created_at', '>=', $this->filtroFechaInicio);
        }

        if (!empty($this->filtroFechaFin)) {
            $query->whereDate('ai.created_at', '<=', $this->filtroFechaFin);
        }

        // Ordenamiento
        if ($this->ordenarPor === 'usuario_nombre') {
            $query->orderBy('users.name', $this->direccionOrden);
        } else {
            $query->orderBy('ai.' . $this->ordenarPor, $this->direccionOrden);
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
        $ajustes = $this->obtenerAjustes();

        return view('livewire.reporte.ajuste-de-unidades', [
            'ajustes' => $ajustes
        ]);
    }
}
