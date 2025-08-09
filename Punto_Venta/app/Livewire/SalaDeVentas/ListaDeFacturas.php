<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Factura;

class ListaDeFacturas extends Component
{
    public $facturaParaImprimir = null;
    public $productosFacturaImpresa = [];
    public $pagosFacturaImpresa = [];
    public $caiFacturaImpresa = null;
    public $facturaDetalle = null;

    public function verDetalle($facturaId)
    {
        $this->facturaDetalle = Factura::find($facturaId);
    }

    public function cerrarDetalle()
    {
        $this->facturaDetalle = null;
    }

    public function generarPDF($facturaId)
    {
        return redirect()->route('factura.pdf', $facturaId);
    }

    private function cargarDatosParaImpresion($facturaId)
    {
        // Cargar la factura
        $this->facturaParaImprimir = Factura::find($facturaId);

        // Cargar información del CAI asociado a la factura
        $this->caiFacturaImpresa = DB::table('cai')
            ->where('id', $this->facturaParaImprimir->cai_id)
            ->first();

        // Cargar productos
        $this->productosFacturaImpresa = DB::table('factura_has_producto as fp')
            ->join('producto as p', 'fp.producto_id', '=', 'p.id')
            ->where('fp.factura_id', $facturaId)
            ->select(
                'p.nombre',
                'p.codigo_barra',
                'fp.cantidad',
                'fp.precio_unidad',
                'fp.subtotal',
                'fp.descuento',
                'fp.isv_aplicado',
                'fp.isv',
                'fp.total'
            )
            ->get();

        // Cargar métodos de pago
        $this->pagosFacturaImpresa = DB::table('factura_has_pago as fp')
            ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
            ->where('fp.factura_id', $facturaId)
            ->select('tp.nombre as metodo', 'fp.pago_recibido')
            ->get();
    }

    public function cerrarImpresion()
    {
        $this->facturaParaImprimir = null;
        $this->productosFacturaImpresa = [];
        $this->pagosFacturaImpresa = [];
        $this->caiFacturaImpresa = null;
    }

    public function render()
    {
        $facturas = DB::table('factura as f')
            ->select(
                'f.id',
                'f.numero_factura',
                'f.fecha_emision',
                'f.sub_total',
                'f.isv',
                'f.total',
                'f.estado_factura_id',
                'f.nombre_cliente',
                'f.rtn'
            )
            ->orderBy('f.fecha_emision', 'desc')
            ->get();

        return view('livewire.sala-de-ventas.lista-de-facturas', [
            'facturas' => $facturas
        ]);
    }
}
