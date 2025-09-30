<?php

namespace App\Excel;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;

class DetalleCompraExport implements FromView, ShouldAutoSize
{
    private $detalle;
    private $fechaGeneracion;
    private $usuarioReporte;

    public function __construct($detalle, $fechaGeneracion, $usuarioReporte)
    {
        $this->detalle = $detalle;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->usuarioReporte = $usuarioReporte;
    }

    public function view(): View
    {
        return view('exports.detalle-compra', [
            'detalle' => $this->detalle,
            'fechaGeneracion' => $this->fechaGeneracion,
            'usuarioReporte' => $this->usuarioReporte,
        ]);
    }
}
