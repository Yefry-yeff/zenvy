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
            // Cargar la factura usando el modelo (igual que en el componente Livewire)
            $factura = \App\Models\Factura::find($facturaId);
            
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
                    'fp.subtotal',
                    'fp.descuento',
                    'fp.isv_aplicado',
                    'fp.isv',
                    'fp.total'
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
            
            // Cargar datos de tienda con dirección (igual que en el componente Livewire)
            $tienda = DB::table('tienda as t')
                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                ->select('t.*', 'd.domicilio_tributario')
                ->where('t.id', 1)
                ->first();
            
            // Como usamos el modelo Factura, fecha_emision ya viene como Carbon
            // No necesitamos convertir la fecha
            
            // Generar el PDF
            $pdf = Pdf::loadView('pdf.factura', compact(
                'factura', 
                'productos', 
                'pagos', 
                'empresa', 
                'tienda', 
                'caiFacturaImpresa'
            ))
            ->setPaper([0, 0, 204.4, 595.3], 'portrait') // 72.1mm x 210mm
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'debugKeepTemp' => false,
                'isFontSubsettingEnabled' => false,
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
            // Cargar la factura usando el modelo (igual que en el componente Livewire)
            $factura = \App\Models\Factura::find($facturaId);
            
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
                    'fp.subtotal',
                    'fp.descuento',
                    'fp.isv_aplicado',
                    'fp.isv',
                    'fp.total'
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
            
            // Cargar datos de tienda con dirección (igual que en el componente Livewire)
            $tienda = DB::table('tienda as t')
                ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
                ->select('t.*', 'd.domicilio_tributario')
                ->where('t.id', 1)
                ->first();
            
            // Como usamos el modelo Factura, fecha_emision ya viene como Carbon
            // No necesitamos convertir la fecha
            
            // Generar el PDF para previsualización (inline)
            $pdf = Pdf::loadView('pdf.factura', compact(
                'factura', 
                'productos', 
                'pagos', 
                'empresa', 
                'tienda', 
                'caiFacturaImpresa'
            ))
            ->setPaper([0, 0, 204.4, 595.3], 'portrait') // 72.1mm x 210mm
            ->setOptions([
                'defaultFont' => 'Arial',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'debugKeepTemp' => false,
                'isFontSubsettingEnabled' => false,
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
