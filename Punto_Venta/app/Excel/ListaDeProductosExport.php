<?php

namespace App\Excel;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\View\View;

class ListaDeProductosExport implements FromView, WithStyles, ShouldAutoSize
{
    private $productos;
    private $fechaGeneracion;
    private $totalProductos;
    private $filtrosAplicados;
    private $usuarioReporte;

    public function __construct($productos, $fechaGeneracion, $totalProductos, $filtrosAplicados, $usuarioReporte)
    {
        $this->productos = $productos;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->totalProductos = $totalProductos;
        $this->filtrosAplicados = $filtrosAplicados;
        $this->usuarioReporte = $usuarioReporte;
    }

    public function view(): View
    {
        return view('exports.lista-de-productos', [
            'productos' => $this->productos,
            'fechaGeneracion' => $this->fechaGeneracion,
            'totalProductos' => $this->totalProductos,
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
