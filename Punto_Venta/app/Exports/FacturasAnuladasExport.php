<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacturasAnuladasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $facturasAnuladas;

    public function __construct($facturasAnuladas)
    {
        $this->facturasAnuladas = collect($facturasAnuladas);
    }

    public function collection()
    {
        return $this->facturasAnuladas;
    }

    public function headings(): array
    {
        return [
            'ID',
            'N° Factura',
            'Cliente',
            'RTN',
            'Fecha Emisión Original',
            'Fecha Anulación',
            'Subtotal',
            'ISV',
            'Total',
            'Vendedor Original',
            'Usuario que Anuló',
            'Motivo Anulación',
            'Método Devolución',
            'Impacto Flujo Caja',
            'Observaciones'
        ];
    }

    public function map($facturaAnulada): array
    {
        return [
            $facturaAnulada->id,
            $facturaAnulada->numero_factura,
            $facturaAnulada->nombre_cliente ?? 'N/A',
            $facturaAnulada->rtn ?? 'N/A',
            \Carbon\Carbon::parse($facturaAnulada->fecha_emision_factura)->format('d/m/Y'),
            \Carbon\Carbon::parse($facturaAnulada->fecha_anulacion)->format('d/m/Y H:i:s'),
            number_format($facturaAnulada->sub_total, 2),
            number_format($facturaAnulada->isv, 2),
            number_format($facturaAnulada->total, 2),
            $facturaAnulada->vendedor_nombre ?? 'N/A',
            $facturaAnulada->usuario_anulo_nombre ?? 'N/A',
            $facturaAnulada->motivo_anulacion,
            $facturaAnulada->metodo_devolucion ?? 'N/A',
            $facturaAnulada->impacto_flujo_caja ? number_format($facturaAnulada->impacto_flujo_caja, 2) : 'N/A',
            $facturaAnulada->observaciones ?? 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'Facturas Anuladas';
    }
}
