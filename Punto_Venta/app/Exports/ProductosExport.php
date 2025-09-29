<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductosExport implements FromView, WithStyles, ShouldAutoSize
{
    protected $productos;
    protected $fechaGeneracion;
    protected $totalProductos;
    protected $filtrosAplicados;

    public function __construct($productos, $fechaGeneracion, $totalProductos, $filtrosAplicados)
    {
        $this->productos = $productos;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->totalProductos = $totalProductos;
        $this->filtrosAplicados = $filtrosAplicados;
    }

    public function view(): View
    {
        return view('exports.productos', [
            'productos' => $this->productos,
            'fechaGeneracion' => $this->fechaGeneracion,
            'totalProductos' => $this->totalProductos,
            'filtrosAplicados' => $this->filtrosAplicados
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el título
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
            // Estilo para encabezados (asumiendo que están en la fila 6)
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