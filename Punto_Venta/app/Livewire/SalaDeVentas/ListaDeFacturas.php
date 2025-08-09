<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ListaDeFacturas extends Component
{
    use WithPagination;

    public $fechaDesde;
    public $fechaHasta;

    public function mount()
    {
        // Establecer fechas por defecto (últimos 30 días)
        $this->fechaHasta = now()->format('Y-m-d');
        $this->fechaDesde = now()->subDays(30)->format('Y-m-d');
    }

    public function filtrar()
    {
        $this->resetPage();
    }

    public function imprimirFactura($facturaId)
    {
        // Esta función se implementará después
        session()->flash('message', "Imprimir factura ID: {$facturaId}");
    }

    public function render()
    {
        $facturas = DB::table('factura as f')
            ->leftJoin('cliente as c', 'f.cliente_id', '=', 'c.id')
            ->select(
                'f.id',
                'f.numero_factura',
                'f.fecha_emision',
                'f.subtotal',
                'f.isv_total',
                'f.total',
                'f.estado_id',
                'c.nombre as cliente_nombre',
                'c.rtn as cliente_rtn'
            )
            ->when($this->fechaDesde, function($query) {
                return $query->whereDate('f.fecha_emision', '>=', $this->fechaDesde);
            })
            ->when($this->fechaHasta, function($query) {
                return $query->whereDate('f.fecha_emision', '<=', $this->fechaHasta);
            })
            ->orderBy('f.fecha_emision', 'desc')
            ->paginate(10);

        return view('livewire.sala-de-ventas.lista-de-facturas', [
            'facturas' => $facturas
        ]);
    }
}
