<?php

namespace App\Excel;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GestionDiferenciasExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $diferencias;
    protected $fechaGeneracion;
    protected $usuarioReporte;
    protected $nombreTienda;

    public function __construct($diferencias, $fechaGeneracion, $usuarioReporte, $nombreTienda)
    {
        $this->diferencias = $diferencias;
        $this->fechaGeneracion = $fechaGeneracion;
        $this->usuarioReporte = $usuarioReporte;
        $this->nombreTienda = $nombreTienda;
    }

    public function collection()
    {
        return $this->diferencias->map(function($diferencia, $index) {
            $tipoCierre = $diferencia->tipo_cierre == 1 ? 'Cierre de Caja' : 'Cierre de Jornada';
            $tipoDiferencia = $diferencia->diferencia_efectivo > 0 ? 'Sobrante' : 'Faltante';
            $estado = abs($diferencia->diferencia_efectivo) < 0.01 ? 'Resuelto' : 'Pendiente';
            
            return [
                'N°' => $index + 1,
                'ID Cierre' => $diferencia->cierre_id,
                'Caja' => '#' . $diferencia->caja_id,
                'Usuario' => $diferencia->nombre_usuario,
                'Tipo de Cierre' => $tipoCierre,
                'Fecha del Cierre' => \Carbon\Carbon::parse($diferencia->created_at)->format('d/m/Y H:i:s'),
                'Total Esperado' => 'L. ' . number_format($diferencia->total_efectivo, 2),
                'Total Contado' => 'L. ' . number_format($diferencia->conteo_efectivo, 2),
                'Diferencia Original' => 'L. ' . number_format($diferencia->diferencia_efectivo, 2),
                'Tipo' => $tipoDiferencia,
                'Total Gestionado' => 'L. ' . number_format($diferencia->total_gestionado, 2),
                'Gestiones Realizadas' => $diferencia->gestiones_realizadas,
                'Diferencia Pendiente' => 'L. ' . number_format(abs($diferencia->diferencia_efectivo), 2),
                'Estado' => $estado,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'N°',
            'ID Cierre',
            'Caja',
            'Usuario',
            'Tipo de Cierre',
            'Fecha del Cierre',
            'Total Esperado',
            'Total Contado',
            'Diferencia Original',
            'Tipo',
            'Total Gestionado',
            'Gestiones Realizadas',
            'Diferencia Pendiente',
            'Estado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A7:N7')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EA580C'], // Color naranja
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [
            7 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Título del reporte
                $sheet->mergeCells('A1:N1');
                $sheet->setCellValue('A1', 'REPORTE DE GESTIÓN DE DIFERENCIAS');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'EA580C'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Información del reporte
                $sheet->mergeCells('A2:N2');
                $sheet->setCellValue('A2', 'Tienda: ' . $this->nombreTienda);
                $sheet->getStyle('A2')->getFont()->setBold(true);

                $sheet->mergeCells('A3:N3');
                $sheet->setCellValue('A3', 'Fecha de generación: ' . $this->fechaGeneracion);
                
                $sheet->mergeCells('A4:N4');
                $sheet->setCellValue('A4', 'Generado por: ' . $this->usuarioReporte);
                
                $sheet->mergeCells('A5:N5');
                $sheet->setCellValue('A5', 'Total de registros: ' . $this->diferencias->count());
                $sheet->getStyle('A5')->getFont()->setBold(true);

                // Línea en blanco
                $sheet->getRowDimension(6)->setRowHeight(5);

                // Auto-ajustar columnas
                foreach(range('A', 'N') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Aplicar bordes a todas las celdas con datos
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A7:N' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ]);

                // Centrar columnas numéricas
                $sheet->getStyle('A8:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B8:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C8:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('L8:L' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('N8:N' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
