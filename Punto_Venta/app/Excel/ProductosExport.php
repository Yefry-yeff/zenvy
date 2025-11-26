<?php

namespace App\Excel;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class ProductosExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    private $query;
    private $fechaGeneracion;
    private $totalProductos;
    private $filtrosAplicados;
    private $usuarioReporte;

    public function __construct($query, $fechaGeneracion, $totalProductos, $filtrosAplicados, $usuarioReporte)
    {
        $this->query = $query;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->totalProductos = $totalProductos;
        $this->filtrosAplicados = $filtrosAplicados;
        $this->usuarioReporte = $usuarioReporte;
    }

    public function collection()
    {
        $rows = new Collection();
        
        // Procesar en chunks para evitar sobrecarga de memoria
        $this->query->chunk(200, function ($productos) use ($rows) {
            foreach ($productos as $producto) {
                $preciosVenta = $producto->preciosVenta;
                
                if ($preciosVenta->count() > 0) {
                    foreach ($preciosVenta as $precioVenta) {
                        $rows->push([
                            $producto->id,
                            $precioVenta->codigo_barra ?? 'Sin código', // Se formateará como texto con columnFormats()
                            $producto->nombre,
                            $producto->marca->nombre ?? 'Sin marca',
                            $producto->subcategoria->categoria->nombre ?? 'N/A',
                            $producto->subcategoria->nombre ?? 'N/A',
                            $precioVenta->unidadMedida->nombre ?? 'N/A',
                            number_format($precioVenta->precio, 2),
                            $precioVenta->cantidad ?? 1,
                            $producto->producto_valencia ? 'Valencia' : 'Paperland',
                        ]);
                    }
                } else {
                    $rows->push([
                        $producto->id,
                        'Sin código', // Se formateará como texto con columnFormats()
                        $producto->nombre,
                        $producto->marca->nombre ?? 'Sin marca',
                        $producto->subcategoria->categoria->nombre ?? 'N/A',
                        $producto->subcategoria->nombre ?? 'N/A',
                        'Sin precio de venta',
                        '-',
                        '-',
                        $producto->producto_valencia ? 'Valencia' : 'Paperland',
                    ]);
                }
            }
        });
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            ['📦 LISTADO DE PRODUCTOS'],
            ['Sistema ZENVY - Gestión de Inventario'],
            [''],
            ["📅 Generado el: {$this->fechaGeneracion} | 👤 Usuario: {$this->usuarioReporte} | 📊 Total: {$this->totalProductos} productos | 🔍 Filtros: {$this->filtrosAplicados}"],
            [''],
            [
                'ID',
                'Código de Barras',
                'Nombre',
                'Marca',
                'Categoría',
                'Subcategoría',
                'Unidad de Medida',
                'Precio de Venta',
                'Cantidad por Unidad',
                'Origen'
            ]
        ];
    }

    public function chunkSize(): int
    {
        return 500; // Procesar 500 registros a la vez
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // Columna B = Código de Barras como texto
        ];
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
