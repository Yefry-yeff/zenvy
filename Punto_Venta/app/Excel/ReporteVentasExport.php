<?php

namespace App\Excel;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteVentasExport implements FromView, WithStyles, ShouldAutoSize
{
    private $facturas;
    private $totales;
    private $fechaGeneracion;
    private $filtrosAplicados;

    public function __construct($facturas, $totales, $fechaGeneracion, $filtrosAplicados)
    {
        $this->facturas         = $facturas;
        $this->totales          = $totales;
        $this->fechaGeneracion  = $fechaGeneracion;
        $this->filtrosAplicados = $filtrosAplicados;
    }

    public function view(): View
    {
        return view('exports.reporte-ventas', [
            'facturas'         => $this->facturas,
            'totales'          => $this->totales,
            'fechaGeneracion'  => $this->fechaGeneracion,
            'filtrosAplicados' => $this->filtrosAplicados,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            6 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
        ];
    }
}
