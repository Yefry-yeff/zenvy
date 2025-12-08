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
                    DB::raw('SUM(fhp.total_factura) as total')
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

            // Obtener detalles de tarjetas (facturas individuales)
            $detallesTarjeta = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->where('tp.nombre', 'like', '%tarjeta%')
                ->select('f.numero_factura', 'fhp.total_factura as monto', 'f.created_at')
                ->orderBy('f.created_at')
                ->get();

            // Obtener detalles de transferencias
            $detallesTransferencia = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->where('tp.nombre', 'like', '%transferencia%')
                ->select('f.numero_factura', 'fhp.total_factura as monto', 'f.created_at')
                ->orderBy('f.created_at')
                ->get();

            // Obtener detalles de cheques
            $detallesCheque = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->where('tp.nombre', 'like', '%cheque%')
                ->select('f.numero_factura', 'fhp.total_factura as monto', 'f.created_at')
                ->orderBy('f.created_at')
                ->get();

            // Calcular depósito (diferencia de L.2000)
            $montoDeposito = $cierre->total_efectivo_contado - 2000;

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
                'detallesTarjeta',
                'detallesTransferencia',
                'detallesCheque',
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
                    DB::raw('SUM(fhp.total_factura) as total')
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

            // Obtener detalles de tarjetas, transferencias y cheques
            $detallesTarjeta = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->where('tp.nombre', 'like', '%tarjeta%')
                ->select('f.numero_factura', 'fhp.total_factura as monto', 'f.created_at')
                ->orderBy('f.created_at')
                ->get();

            $detallesTransferencia = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->where('tp.nombre', 'like', '%transferencia%')
                ->select('f.numero_factura', 'fhp.total_factura as monto', 'f.created_at')
                ->orderBy('f.created_at')
                ->get();

            $detallesCheque = DB::table('factura as f')
                ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
                ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
                ->where('f.users_id', $cierre->user_id)
                ->whereBetween('f.created_at', [$fechaInicio, $fechaFin])
                ->where('tp.nombre', 'like', '%cheque%')
                ->select('f.numero_factura', 'fhp.total_factura as monto', 'f.created_at')
                ->orderBy('f.created_at')
                ->get();

            // Calcular depósito (diferencia de L.2000)
            $montoDeposito = $cierre->total_efectivo_contado - 2000;

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
                'detallesTarjeta',
                'detallesTransferencia',
                'detallesCheque',
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
}
