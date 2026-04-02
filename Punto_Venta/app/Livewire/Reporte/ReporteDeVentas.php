<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Factura;
use App\Excel\ReporteVentasExport;

class ReporteDeVentas extends Component
{
    // Filtros principales
    public $fechaInicio = '';
    public $fechaFinal = '';
    public $filtroCliente = '';
    public $filtroMonto = '';
    public $filtroNumeroFactura = '';

    // Filtros por columna adicionales
    public $filtroId = '';
    public $filtroRTN = '';

    // Ordenamiento
    public $ordenarPor = 'fecha_emision';
    public $direccionOrden = 'desc';

    // Paginación
    public $registrosPorPagina = 10;
    public $page = 1;

    // Estado
    public $cargando = false;

    public function mount()
    {
        $this->fechaInicio = now()->startOfMonth()->format('Y-m-d');
        $this->fechaFinal  = now()->format('Y-m-d');
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

    public function resetPage() { $this->page = 1; }
    public function nextPage() { $this->page++; }
    public function previousPage() { if ($this->page > 1) $this->page--; }
    public function gotoPage($p) { $this->page = $p; }

    public function updatedFechaInicio()       { $this->resetPage(); }
    public function updatedFechaFinal()        { $this->resetPage(); }
    public function updatedFiltroCliente()     { $this->resetPage(); }
    public function updatedFiltroMonto()       { $this->resetPage(); }
    public function updatedFiltroNumeroFactura(){ $this->resetPage(); }
    public function updatedFiltroId()          { $this->resetPage(); }
    public function updatedFiltroRTN()         { $this->resetPage(); }
    public function updatedRegistrosPorPagina(){ $this->resetPage(); }

    public function limpiarFiltros()
    {
        $this->fechaInicio        = now()->startOfMonth()->format('Y-m-d');
        $this->fechaFinal         = now()->format('Y-m-d');
        $this->filtroCliente      = '';
        $this->filtroMonto        = '';
        $this->filtroNumeroFactura = '';
        $this->filtroId           = '';
        $this->filtroRTN          = '';
        $this->resetPage();
    }

    private function buildQuery()
    {
        $query = Factura::query()->where('estado_factura_id', 1); // solo activas

        if (!empty($this->fechaInicio)) {
            $query->whereDate('fecha_emision', '>=', $this->fechaInicio);
        }
        if (!empty($this->fechaFinal)) {
            $query->whereDate('fecha_emision', '<=', $this->fechaFinal);
        }
        if (!empty($this->filtroCliente)) {
            $query->where('nombre_cliente', 'like', '%' . $this->filtroCliente . '%');
        }
        if (!empty($this->filtroMonto)) {
            $query->where('total', 'like', '%' . $this->filtroMonto . '%');
        }
        if (!empty($this->filtroNumeroFactura)) {
            $query->where('numero_factura', 'like', '%' . $this->filtroNumeroFactura . '%');
        }
        if (!empty($this->filtroId)) {
            $query->where('id', $this->filtroId);
        }
        if (!empty($this->filtroRTN)) {
            $query->where('rtn', 'like', '%' . $this->filtroRTN . '%');
        }

        return $query->orderBy($this->ordenarPor, $this->direccionOrden);
    }

    private function getTotales()
    {
        return $this->buildQuery()->selectRaw(
            'SUM(sub_total_grabado) as total_gravado,
             SUM(sub_total_exento)  as total_exento,
             SUM(monto_descuento)   as total_descuento,
             SUM(sub_total)         as total_subtotal,
             SUM(isv)               as total_isv,
             SUM(total)             as total_total'
        )->first();
    }

    private function obtenerFiltrosAplicados()
    {
        $filtros = [];
        if (!empty($this->fechaInicio))         $filtros[] = "Desde: {$this->fechaInicio}";
        if (!empty($this->fechaFinal))          $filtros[] = "Hasta: {$this->fechaFinal}";
        if (!empty($this->filtroCliente))       $filtros[] = "Cliente: '{$this->filtroCliente}'";
        if (!empty($this->filtroNumeroFactura)) $filtros[] = "N° Factura: '{$this->filtroNumeroFactura}'";
        if (!empty($this->filtroMonto))         $filtros[] = "Monto: '{$this->filtroMonto}'";
        if (!empty($this->filtroId))            $filtros[] = "ID: '{$this->filtroId}'";
        if (!empty($this->filtroRTN))           $filtros[] = "RTN: '{$this->filtroRTN}'";
        return empty($filtros) ? 'Ninguno' : implode(', ', $filtros);
    }

    public function descargarExcel()
    {
        try {
            $facturas = $this->buildQuery()->get();
            $totales  = $this->getTotales();

            $timestamp = now()->format('Y-m-d_H-i-s');
            return Excel::download(
                new ReporteVentasExport(
                    $facturas,
                    $totales,
                    now()->format('d/m/Y H:i:s'),
                    $this->obtenerFiltrosAplicados()
                ),
                "reporte_ventas_{$timestamp}.xlsx"
            );
        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar Excel: ' . $e->getMessage());
            Log::error('Error generando Excel reporte ventas', ['error' => $e->getMessage()]);
        }
    }

    public function descargarPDF()
    {
        try {
            $facturas       = $this->buildQuery()->get();
            $totales        = $this->getTotales();
            $totalRegistros = $facturas->count();

            $pdf = Pdf::loadView('pdf.reporte-ventas', [
                'facturas'         => $facturas,
                'totales'          => $totales,
                'totalRegistros'   => $totalRegistros,
                'fechaGeneracion'  => now()->format('d/m/Y H:i:s'),
                'filtrosAplicados' => $this->obtenerFiltrosAplicados(),
            ]);

            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont'          => 'Arial',
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
            ]);

            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename  = "reporte_ventas_{$timestamp}.pdf";
            $tempDir   = storage_path('app/temp');

            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $pdf->save($tempDir . '/' . $filename);

            return redirect()->route('download.file', ['file' => $filename]);
        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar PDF: ' . $e->getMessage());
            Log::error('Error generando PDF reporte ventas', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $facturas = $this->buildQuery()->paginate($this->registrosPorPagina, ['*'], 'page', $this->page);
        $totales  = $this->getTotales();

        return view('livewire.reporte.reporte-de-ventas', compact('facturas', 'totales'));
    }
}
