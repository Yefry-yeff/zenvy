<?php

namespace App\Excel;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacturasExport implements FromView, WithStyles, ShouldAutoSize
{
    private $facturas;
    private $fechaGeneracion;
    private $totalFacturas;
    private $filtrosAplicados;
    private $usuarioReporte;

    public function __construct($facturas, $fechaGeneracion, $totalFacturas, $filtrosAplicados, $usuarioReporte)
    {
        $this->facturas = $facturas;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->totalFacturas = $totalFacturas;
        $this->filtrosAplicados = $filtrosAplicados;
        $this->usuarioReporte = $usuarioReporte;
    }

    public function view(): View
    {
        return view('exports.facturas', [
            'facturas' => $this->facturas,
            'fechaGeneracion' => $this->fechaGeneracion,
            'totalFacturas' => $this->totalFacturas,
            'filtrosAplicados' => $this->filtrosAplicados,
            'usuarioReporte' => $this->usuarioReporte,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ],
            6 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '4472C4'
                    ]
                ]
            ]
        ];
    }
}
