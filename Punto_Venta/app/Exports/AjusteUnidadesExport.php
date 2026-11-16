<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AjusteUnidadesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $ajustes;

    public function __construct($ajustes)
    {
        $this->ajustes = $ajustes;
    }

    public function collection()
    {
        return $this->ajustes;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Producto',
            'Código Producto',
            'Bodega',
            'Sección',
            'Tipo de Ajuste',
            'Cantidad Anterior',
            'Cantidad Ajustada',
            'Cantidad Nueva',
            'Unidad de Medida',
            'Usuario',
            'Motivo'
        ];
    }

    public function map($ajuste): array
    {
        $tipoAjuste = strtoupper($ajuste->tipo_ajuste);
        $cantidadAjustada = ($ajuste->tipo_ajuste === 'aumentar' ? '+' : '-') . number_format($ajuste->cantidad_ajustada, 2);

        return [
            Carbon::parse($ajuste->fecha_ajuste)->format('d/m/Y'),
            Carbon::parse($ajuste->fecha_ajuste)->format('h:i A'),
            $ajuste->producto_nombre,
            $ajuste->producto_id,
            $ajuste->bodega_nombre,
            $ajuste->seccion_nombre,
            $tipoAjuste,
            number_format($ajuste->cantidad_anterior, 2),
            $cantidadAjustada,
            number_format($ajuste->cantidad_nueva, 2),
            $ajuste->unidad_medida,
            $ajuste->usuario_nombre,
            $ajuste->motivo
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Fecha
            'B' => 10,  // Hora
            'C' => 35,  // Producto
            'D' => 15,  // Código Producto
            'E' => 25,  // Bodega
            'F' => 25,  // Sección
            'G' => 15,  // Tipo de Ajuste
            'H' => 18,  // Cantidad Anterior
            'I' => 18,  // Cantidad Ajustada
            'J' => 18,  // Cantidad Nueva
            'K' => 18,  // Unidad de Medida
            'L' => 25,  // Usuario
            'M' => 50,  // Motivo
        ];
    }
}
