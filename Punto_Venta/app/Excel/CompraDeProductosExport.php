<?php

namespace App\Excel;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\View\View;

class CompraDeProductosExport implements FromView, WithStyles, ShouldAutoSize
{
    private $compras;
    private $fechaGeneracion;
    private $totalCompras;
    private $filtrosAplicados;
    private $usuarioReporte;

    public function __construct($compras, $fechaGeneracion, $totalCompras, $filtrosAplicados, $usuarioReporte)
    {
        $this->compras = $compras;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->totalCompras = $totalCompras;
        $this->filtrosAplicados = $filtrosAplicados;
        $this->usuarioReporte = $usuarioReporte;
    }

    public function view(): View
    {
        return view('exports.compra-de-productos', [
            'compras' => $this->compras,
            'fechaGeneracion' => $this->fechaGeneracion,
            'totalCompras' => $this->totalCompras,
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
