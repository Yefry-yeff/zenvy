<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class CierreCajaPDFController extends Controller
{
    public function generarPDF($cierreId)
    {
        try {
            // Cargar el cierre de caja
            $cierre = DB::table('cierre_caja_historico')->where('id', $cierreId)->first();

            if (!$cierre) {
                abort(404, 'Cierre de caja no encontrado');
            }

            // Cargar usuario
            $usuario = DB::table('users')->where('id', $cierre->user_id)->first();

            // Determinar rango de fechas del cierre
            $fechaInicio = $cierre->periodo_inicio ? \Carbon\Carbon::parse($cierre->periodo_inicio) : \Carbon\Carbon::parse($cierre->fecha_cierre)->startOfDay();
            $fechaFin = \Carbon\Carbon::parse($cierre->fecha_cierre);

            // Cargar resumen de transacciones desde factura_has_pago
            $resumenTransacciones = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->select(
                    'tp.nombre as forma_pago',
                    DB::raw('SUM(fhp.monto) as total')
                )
                ->groupBy('tp.id', 'tp.nombre')
                ->get();

            // Preparar denominaciones (solo las que tienen cantidad > 0)
            $denominaciones = [];
            $denominacionesConfig = [
                ['campo' => 'billetes_500', 'denominacion' => 'L. 500'],
                ['campo' => 'billetes_200', 'denominacion' => 'L. 200'],
                ['campo' => 'billetes_100', 'denominacion' => 'L. 100'],
                ['campo' => 'billetes_50', 'denominacion' => 'L. 50'],
                ['campo' => 'billetes_20', 'denominacion' => 'L. 20'],
                ['campo' => 'billetes_10', 'denominacion' => 'L. 10'],
                ['campo' => 'billetes_5', 'denominacion' => 'L. 5'],
                ['campo' => 'billetes_2', 'denominacion' => 'L. 2'],
                ['campo' => 'billetes_1', 'denominacion' => 'L. 1'],
                ['campo' => 'monedas_0_50', 'denominacion' => 'L. 0.50'],
                ['campo' => 'monedas_0_20', 'denominacion' => 'L. 0.20'],
                ['campo' => 'monedas_0_10', 'denominacion' => 'L. 0.10'],
                ['campo' => 'monedas_0_05', 'denominacion' => 'L. 0.05'],
            ];

            foreach ($denominacionesConfig as $config) {
                $cantidad = $cierre->{$config['campo']} ?? 0;
                if ($cantidad > 0) {
                    // Calcular el valor numérico de la denominación
                    $valor = (float) str_replace(['L. ', ','], '', $config['denominacion']);
                    $denominaciones[] = [
                        'denominacion' => $config['denominacion'],
                        'cantidad' => $cantidad,
                        'total' => $cantidad * $valor
                    ];
                }
            }

            // Calcular depósito (diferencia de L.2000, nunca negativo)
            $montoDeposito = max(0, $cierre->total_efectivo_contado - 2000);

            // Cargar datos de empresa
            $empresa = DB::table('empresa')->first();

            // Cargar datos de tienda
            $tienda = DB::table('tienda as t')
                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                ->select('t.*', 'd.domicilio_tributario')
                ->where('t.id', 1)
                ->first();

            // Generar el PDF
            $pdf = Pdf::loadView('pdf.recibo-cierre-caja', compact(
                'cierre',
                'usuario',
                'resumenTransacciones',
                'denominaciones',
                'empresa',
                'tienda',
                'montoDeposito'
            ))
            ->setPaper([0, 0, 204.4, 992.1], 'portrait') // 72.1mm x 350mm
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'debugKeepTemp' => false,
                'isFontSubsettingEnabled' => false,
            ]);

            $fecha = \Carbon\Carbon::parse($cierre->fecha_cierre)->format('Ymd_His');
            $fileName = "cierre_caja_{$fecha}.pdf";

            return $pdf->download($fileName);

        } catch (Exception $e) {
            Log::error("Error al generar PDF de cierre de caja: " . $e->getMessage(), [
                'cierre_id' => $cierreId,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al generar el recibo: ' . $e->getMessage());
        }
    }

    public function previsualizarPDF($cierreId)
    {
        try {
            // Cargar el cierre de caja
            $cierre = DB::table('cierre_caja_historico')->where('id', $cierreId)->first();

            if (!$cierre) {
                abort(404, 'Cierre de caja no encontrado');
            }

            // Cargar usuario
            $usuario = DB::table('users')->where('id', $cierre->user_id)->first();

            // Determinar rango de fechas del cierre
            $fechaInicio = $cierre->periodo_inicio ? \Carbon\Carbon::parse($cierre->periodo_inicio) : \Carbon\Carbon::parse($cierre->fecha_cierre)->startOfDay();
            $fechaFin = \Carbon\Carbon::parse($cierre->fecha_cierre);

            // Cargar resumen de transacciones desde factura_has_pago
            $resumenTransacciones = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->select(
                    'tp.nombre as forma_pago',
                    DB::raw('SUM(fhp.monto) as total')
                )
                ->groupBy('tp.id', 'tp.nombre')
                ->get();

            // Preparar denominaciones (solo las que tienen cantidad > 0)
            $denominaciones = [];
            $denominacionesConfig = [
                ['campo' => 'billetes_500', 'denominacion' => 'L. 500'],
                ['campo' => 'billetes_200', 'denominacion' => 'L. 200'],
                ['campo' => 'billetes_100', 'denominacion' => 'L. 100'],
                ['campo' => 'billetes_50', 'denominacion' => 'L. 50'],
                ['campo' => 'billetes_20', 'denominacion' => 'L. 20'],
                ['campo' => 'billetes_10', 'denominacion' => 'L. 10'],
                ['campo' => 'billetes_5', 'denominacion' => 'L. 5'],
                ['campo' => 'billetes_2', 'denominacion' => 'L. 2'],
                ['campo' => 'billetes_1', 'denominacion' => 'L. 1'],
                ['campo' => 'monedas_0_50', 'denominacion' => 'L. 0.50'],
                ['campo' => 'monedas_0_20', 'denominacion' => 'L. 0.20'],
                ['campo' => 'monedas_0_10', 'denominacion' => 'L. 0.10'],
                ['campo' => 'monedas_0_05', 'denominacion' => 'L. 0.05'],
            ];

            foreach ($denominacionesConfig as $config) {
                $cantidad = $cierre->{$config['campo']} ?? 0;
                if ($cantidad > 0) {
                    // Calcular el valor numérico de la denominación
                    $valor = (float) str_replace(['L. ', ','], '', $config['denominacion']);
                    $denominaciones[] = [
                        'denominacion' => $config['denominacion'],
                        'cantidad' => $cantidad,
                        'total' => $cantidad * $valor
                    ];
                }
            }

            // Calcular depósito (diferencia de L.2000, nunca negativo)
            $montoDeposito = max(0, $cierre->total_efectivo_contado - 2000);

            // Cargar datos de empresa
            $empresa = DB::table('empresa')->first();

            // Cargar datos de tienda
            $tienda = DB::table('tienda as t')
                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                ->select('t.*', 'd.domicilio_tributario')
                ->where('t.id', 1)
                ->first();

            // Generar el PDF para previsualización
            $pdf = Pdf::loadView('pdf.recibo-cierre-caja', compact(
                'cierre',
                'usuario',
                'resumenTransacciones',
                'denominaciones',
                'empresa',
                'tienda',
                'montoDeposito'
            ))
            ->setPaper([0, 0, 204.4, 992.1], 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'debugKeepTemp' => false,
                'isFontSubsettingEnabled' => false,
            ]);

            return $pdf->stream();

        } catch (Exception $e) {
            Log::error("Error al previsualizar PDF de cierre de caja: " . $e->getMessage(), [
                'cierre_id' => $cierreId,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al previsualizar el recibo: ' . $e->getMessage());
        }
    }

    public function reporteTransacciones($cierreId)
    {
        try {
            // Cargar el cierre de caja
            $cierre = DB::table('cierre_caja_historico')->where('id', $cierreId)->first();

            if (!$cierre) {
                abort(404, 'Cierre de caja no encontrado');
            }

            // Cargar usuario
            $usuario = DB::table('users')->where('id', $cierre->user_id)->first();

            // Determinar rango de fechas del cierre
            $fechaInicio = $cierre->periodo_inicio ? \Carbon\Carbon::parse($cierre->periodo_inicio) : \Carbon\Carbon::parse($cierre->fecha_cierre)->startOfDay();
            $fechaFin = \Carbon\Carbon::parse($cierre->fecha_cierre);

            // Obtener todas las transacciones del periodo
            $transacciones = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->join('cliente as c', 'f.cliente_id', '=', 'c.id')
                ->leftJoin('estado_factura as ef', 'f.estado_factura_id', '=', 'ef.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->select(
                    'f.numero_factura',
                    'f.created_at as fecha_hora',
                    DB::raw("CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente"),
                    'c.rtn',
                    'tp.nombre as forma_pago',
                    'fhp.monto as monto_pago',
                    'f.sub_total',
                    'f.isv',
                    'f.total',
                    'ef.nombre as estado'
                )
                ->orderBy('f.created_at')
                ->get();

            // Crear el archivo Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Configurar encabezado del documento
            $sheet->setCellValue('A1', 'REPORTE DE TRANSACCIONES - CIERRE DE CAJA');
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Información del cierre
            $sheet->setCellValue('A3', 'Cierre ID:');
            $sheet->setCellValue('B3', $cierre->id);
            $sheet->setCellValue('A4', 'Usuario:');
            $sheet->setCellValue('B4', $usuario->name);
            $sheet->setCellValue('A5', 'Fecha Cierre:');
            $sheet->setCellValue('B5', \Carbon\Carbon::parse($cierre->fecha_cierre)->format('d/m/Y H:i:s'));
            $sheet->setCellValue('A6', 'Periodo:');
            $sheet->setCellValue('B6', $fechaInicio->format('d/m/Y H:i') . ' - ' . $fechaFin->format('d/m/Y H:i'));

            // Encabezados de la tabla
            $row = 8;
            $headers = ['#', 'Factura', 'Fecha/Hora', 'Cliente', 'RTN', 'Forma Pago', 'Monto Pago', 'Sub Total', 'ISV', 'Total', 'Estado'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF4472C4');
                $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
                $col++;
            }

            // Datos de transacciones
            $row = 9;
            $contador = 1;
            foreach ($transacciones as $transaccion) {
                $sheet->setCellValue('A' . $row, $contador);
                $sheet->setCellValue('B' . $row, $transaccion->numero_factura);
                $sheet->setCellValue('C' . $row, \Carbon\Carbon::parse($transaccion->fecha_hora)->format('d/m/Y H:i:s'));
                $sheet->setCellValue('D' . $row, $transaccion->cliente);
                $sheet->setCellValue('E' . $row, $transaccion->rtn ?? 'N/A');
                $sheet->setCellValue('F' . $row, $transaccion->forma_pago);
                $sheet->setCellValue('G' . $row, number_format($transaccion->monto_pago, 2));
                $sheet->setCellValue('H' . $row, number_format($transaccion->sub_total, 2));
                $sheet->setCellValue('I' . $row, number_format($transaccion->isv, 2));
                $sheet->setCellValue('J' . $row, number_format($transaccion->total, 2));
                $sheet->setCellValue('K' . $row, $transaccion->estado ?? 'Procesada');
                
                $row++;
                $contador++;
            }

            // Totales
            $row++;
            $sheet->setCellValue('F' . $row, 'TOTALES:');
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            
            $totalEfectivo = $cierre->total_efectivo_contado ?? 0;
            $totalTarjeta = $cierre->total_tarjeta ?? 0;
            $totalTransferencia = $cierre->total_transferencia ?? 0;
            $totalCheque = $cierre->total_cheque ?? 0;
            $totalGeneral = $totalEfectivo + $totalTarjeta + $totalTransferencia + $totalCheque;
            
            $row++;
            $sheet->setCellValue('F' . $row, 'Efectivo:');
            $sheet->setCellValue('G' . $row, 'L. ' . number_format($totalEfectivo, 2));
            $row++;
            $sheet->setCellValue('F' . $row, 'Tarjeta:');
            $sheet->setCellValue('G' . $row, 'L. ' . number_format($totalTarjeta, 2));
            $row++;
            $sheet->setCellValue('F' . $row, 'Transferencia:');
            $sheet->setCellValue('G' . $row, 'L. ' . number_format($totalTransferencia, 2));
            $row++;
            $sheet->setCellValue('F' . $row, 'Cheque:');
            $sheet->setCellValue('G' . $row, 'L. ' . number_format($totalCheque, 2));
            $row++;
            $sheet->setCellValue('F' . $row, 'TOTAL GENERAL:');
            $sheet->setCellValue('G' . $row, 'L. ' . number_format($totalGeneral, 2));
            $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('G' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFE599');

            // Ajustar anchos de columna
            foreach (range('A', 'K') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Generar archivo
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fecha = \Carbon\Carbon::parse($cierre->fecha_cierre)->format('Ymd_His');
            $fileName = "transacciones_cierre_{$fecha}.xlsx";
            
            $tempFile = tempnam(sys_get_temp_dir(), $fileName);
            $writer->save($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error("Error al generar reporte de transacciones: " . $e->getMessage(), [
                'cierre_id' => $cierreId,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }
}
