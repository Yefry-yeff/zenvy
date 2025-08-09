<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class FacturaPDFController extends Controller
{
    public function generarPDF($facturaId)
    {
        try {
            // Cargar la factura
            $factura = DB::table('factura')->where('id', $facturaId)->first();
            
            if (!$factura) {
                abort(404, 'Factura no encontrada');
            }
            
            // Cargar información del CAI asociado a la factura
            $caiFacturaImpresa = DB::table('cai')
                ->where('id', $factura->cai_id)
                ->first();
            
            // Cargar productos
            $productos = DB::table('factura_has_producto as fp')
                ->join('producto as p', 'fp.producto_id', '=', 'p.id')
                ->where('fp.factura_id', $facturaId)
                ->select(
                    'p.nombre',
                    'p.codigo_barra',
                    'fp.cantidad',
                    'fp.precio_unidad',
                    'fp.total',
                    'fp.isv'
                )
                ->get();
                
            // Cargar métodos de pago
            $pagos = DB::table('factura_has_pago as fp')
                ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
                ->where('fp.factura_id', $facturaId)
                ->select('tp.nombre as metodo', 'fp.pago_recibido')
                ->get();
            
            // Cargar datos de empresa
            $empresa = DB::table('empresa')->first();
            
            // Cargar datos de tienda con dirección
            $tienda = null;
            $direccion = null;
            if ($factura->users_id) {
                $tienda = DB::table('users as u')
                    ->join('tienda as t', 'u.tienda_id', '=', 't.id')
                    ->where('u.id', $factura->users_id)
                    ->select('t.*')
                    ->first();
                    
                // Cargar dirección de la tienda
                if ($tienda && $tienda->direccion_sucursal_id) {
                    $direccion = DB::table('direccion')
                        ->where('id', $tienda->direccion_sucursal_id)
                        ->first();
                }
            }
            
            // Convertir fecha_emision a Carbon si es string
            if (is_string($factura->fecha_emision)) {
                $factura->fecha_emision = \Carbon\Carbon::parse($factura->fecha_emision);
            }
            
            // Generar el PDF
            $pdf = Pdf::loadView('pdf.factura', compact(
                'factura', 
                'productos', 
                'pagos', 
                'empresa', 
                'tienda', 
                'direccion',
                'caiFacturaImpresa'
            ))
            ->setPaper([0, 0, 226.77, 800], 'portrait') // 80mm width, auto height
            ->setOptions([
                'defaultFont' => 'Courier',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'debugKeepTemp' => false,
                'chroot' => public_path(),
            ]);
            
            $numeroFactura = str_replace(['/', '-', ' '], '_', $factura->numero_factura);
            $fileName = "factura_{$numeroFactura}.pdf";
            
            return $pdf->download($fileName);
            
        } catch (Exception $e) {
            Log::error("Error al generar PDF de factura: " . $e->getMessage(), [
                'factura_id' => $facturaId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function previsualizarPDF($facturaId)
    {
        try {
            // Cargar la factura
            $factura = DB::table('factura')->where('id', $facturaId)->first();
            
            if (!$factura) {
                abort(404, 'Factura no encontrada');
            }
            
            // Cargar información del CAI asociado a la factura
            $caiFacturaImpresa = DB::table('cai')
                ->where('id', $factura->cai_id)
                ->first();
            
            // Cargar productos
            $productos = DB::table('factura_has_producto as fp')
                ->join('producto as p', 'fp.producto_id', '=', 'p.id')
                ->where('fp.factura_id', $facturaId)
                ->select(
                    'p.nombre',
                    'p.codigo_barra',
                    'fp.cantidad',
                    'fp.precio_unidad',
                    'fp.total',
                    'fp.isv'
                )
                ->get();
                
            // Cargar métodos de pago
            $pagos = DB::table('factura_has_pago as fp')
                ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
                ->where('fp.factura_id', $facturaId)
                ->select('tp.nombre as metodo', 'fp.pago_recibido')
                ->get();
            
            // Cargar datos de empresa
            $empresa = DB::table('empresa')->first();
            
            // Cargar datos de tienda con dirección
            $tienda = null;
            $direccion = null;
            if ($factura->users_id) {
                $tienda = DB::table('users as u')
                    ->join('tienda as t', 'u.tienda_id', '=', 't.id')
                    ->where('u.id', $factura->users_id)
                    ->select('t.*')
                    ->first();
                    
                // Cargar dirección de la tienda
                if ($tienda && $tienda->direccion_sucursal_id) {
                    $direccion = DB::table('direccion')
                        ->where('id', $tienda->direccion_sucursal_id)
                        ->first();
                }
            }
            
            // Convertir fecha_emision a Carbon si es string
            if (is_string($factura->fecha_emision)) {
                $factura->fecha_emision = \Carbon\Carbon::parse($factura->fecha_emision);
            }
            
            // Generar el PDF para previsualización (inline)
            $pdf = Pdf::loadView('pdf.factura', compact(
                'factura', 
                'productos', 
                'pagos', 
                'empresa', 
                'tienda', 
                'direccion',
                'caiFacturaImpresa'
            ))
            ->setPaper([0, 0, 226.77, 800], 'portrait') // 80mm width, auto height
            ->setOptions([
                'defaultFont' => 'Courier',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'debugKeepTemp' => false,
                'chroot' => public_path(),
            ]);
            
            // Mostrar inline en el navegador para embebido
            return $pdf->stream("factura_{$factura->numero_factura}.pdf");
            
        } catch (Exception $e) {
            Log::error("Error al previsualizar PDF de factura: " . $e->getMessage(), [
                'factura_id' => $facturaId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al previsualizar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
