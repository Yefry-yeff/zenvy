<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FacturaAnulada;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FacturasAnuladasExport;

class FacturasAnuladas extends Component
{
    use WithPagination;

    // Filtros
    public $filtroNumeroFactura = '';
    public $filtroCliente = '';
    public $filtroUsuarioAnulo = '';
    public $filtroVendedor = '';
    public $filtroFechaAnulacionInicio = '';
    public $filtroFechaAnulacionFin = '';
    public $filtroFechaEmisionInicio = '';
    public $filtroFechaEmisionFin = '';
    public $filtroMotivo = '';

    // Ordenamiento
    public $ordenarPor = 'fecha_anulacion';
    public $direccionOrden = 'desc';

    // Paginación
    public $porPagina = 15;

    protected $paginationTheme = 'bootstrap';

    // Resetear paginación al cambiar filtros
    public function updatedFiltroNumeroFactura() { $this->resetPage(); }
    public function updatedFiltroCliente() { $this->resetPage(); }
    public function updatedFiltroUsuarioAnulo() { $this->resetPage(); }
    public function updatedFiltroVendedor() { $this->resetPage(); }
    public function updatedFiltroFechaAnulacionInicio() { $this->resetPage(); }
    public function updatedFiltroFechaAnulacionFin() { $this->resetPage(); }
    public function updatedFiltroFechaEmisionInicio() { $this->resetPage(); }
    public function updatedFiltroFechaEmisionFin() { $this->resetPage(); }
    public function updatedFiltroMotivo() { $this->resetPage(); }

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

    public function obtenerFacturasAnuladas()
    {
        $query = FacturaAnulada::query()
            ->join('users as u_anulo', 'facturas_anuladas.users_id_anulo', '=', 'u_anulo.id')
            ->join('users as u_vendedor', 'facturas_anuladas.users_id_vendedor', '=', 'u_vendedor.id')
            ->select(
                'facturas_anuladas.*',
                'u_anulo.name as usuario_anulo_nombre',
                'u_vendedor.name as vendedor_nombre'
            );

        // Aplicar filtros
        if (!empty($this->filtroNumeroFactura)) {
            $query->where('facturas_anuladas.numero_factura', 'like', '%' . $this->filtroNumeroFactura . '%');
        }

        if (!empty($this->filtroCliente)) {
            $query->where('facturas_anuladas.nombre_cliente', 'like', '%' . $this->filtroCliente . '%');
        }

        if (!empty($this->filtroUsuarioAnulo)) {
            $query->where('u_anulo.name', 'like', '%' . $this->filtroUsuarioAnulo . '%');
        }

        if (!empty($this->filtroVendedor)) {
            $query->where('u_vendedor.name', 'like', '%' . $this->filtroVendedor . '%');
        }

        if (!empty($this->filtroFechaAnulacionInicio)) {
            $query->whereDate('facturas_anuladas.fecha_anulacion', '>=', $this->filtroFechaAnulacionInicio);
        }

        if (!empty($this->filtroFechaAnulacionFin)) {
            $query->whereDate('facturas_anuladas.fecha_anulacion', '<=', $this->filtroFechaAnulacionFin);
        }

        if (!empty($this->filtroFechaEmisionInicio)) {
            $query->whereDate('facturas_anuladas.fecha_emision_factura', '>=', $this->filtroFechaEmisionInicio);
        }

        if (!empty($this->filtroFechaEmisionFin)) {
            $query->whereDate('facturas_anuladas.fecha_emision_factura', '<=', $this->filtroFechaEmisionFin);
        }

        if (!empty($this->filtroMotivo)) {
            $query->where('facturas_anuladas.motivo_anulacion', 'like', '%' . $this->filtroMotivo . '%');
        }

        // Ordenamiento
        $query->orderBy('facturas_anuladas.' . $this->ordenarPor, $this->direccionOrden);

        return $query->paginate($this->porPagina);
    }

    public function exportarExcel()
    {
        $facturasAnuladas = $this->obtenerFacturasAnuladas()->items();
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "facturas_anuladas_{$timestamp}.xlsx";

        return Excel::download(new FacturasAnuladasExport($facturasAnuladas), $filename);
    }

    public function render()
    {
        $facturasAnuladas = $this->obtenerFacturasAnuladas();

        // Calcular totales
        $totalMonto = $facturasAnuladas->sum('total');
        $totalRegistros = $facturasAnuladas->total();

        return view('livewire.reporte.facturas-anuladas', [
            'facturasAnuladas' => $facturasAnuladas,
            'totalMonto' => $totalMonto,
            'totalRegistros' => $totalRegistros
        ]);
    }
}
