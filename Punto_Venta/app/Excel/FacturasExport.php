<?php

namespace App\Excel;

use App\Models\Factura;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacturasExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Factura::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'No. Factura',
            'Cliente',
            'RTN',
            'Fecha',
            'Subtotal',
            'ISV',
            'Total',
            'Estado',
        ];
    }

    public function map($factura): array
    {
        return [
            $factura->id,
            $factura->numero_factura,
            $factura->nombre_cliente ?? 'Cliente General',
            $factura->rtn,
            optional($factura->fecha_emision)->format('d/m/Y'),
            number_format($factura->sub_total, 2),
            number_format($factura->isv, 2),
            number_format($factura->total, 2),
            $this->estadoToString($factura->estado_factura_id),
        ];
    }

    private function estadoToString($estadoId)
    {
        return match($estadoId) {
            1 => 'Pagada',
            2 => 'Pendiente',
            3 => 'Anulada',
            default => 'Desconocido',
        };
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        return [];
    }
}
