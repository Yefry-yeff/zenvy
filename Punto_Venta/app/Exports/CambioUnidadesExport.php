<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CambioUnidadesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $cambios;

    public function __construct($cambios)
    {
        $this->cambios = $cambios;
    }

    public function collection()
    {
        return $this->cambios;
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
            'Unidad Original',
            'Cantidad Rebajada',
            'Unidad Nueva',
            'Cantidad Convertida',
            'Factor Conversión',
            'Usuario',
            'Motivo'
        ];
    }

    public function map($cambio): array
    {
        return [
            Carbon::parse($cambio->fecha_cambio)->format('d/m/Y'),
            Carbon::parse($cambio->fecha_cambio)->format('h:i A'),
            $cambio->producto_nombre,
            $cambio->producto_id,
            $cambio->bodega_nombre,
            $cambio->seccion_nombre,
            $cambio->unidad_original,
            number_format($cambio->cantidad_rebajada, 2),
            $cambio->unidad_nueva,
            number_format($cambio->cantidad_convertida, 2),
            $cambio->factor_conversion ? number_format($cambio->factor_conversion, 4) : 'N/A',
            $cambio->usuario_nombre,
            $cambio->motivo ?? 'N/A'
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
                    'startColor' => ['rgb' => '7C3AED']
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
            'G' => 18,  // Unidad Original
            'H' => 18,  // Cantidad Rebajada
            'I' => 18,  // Unidad Nueva
            'J' => 18,  // Cantidad Convertida
            'K' => 18,  // Factor Conversión
            'L' => 25,  // Usuario
            'M' => 50,  // Motivo
        ];
    }
}
