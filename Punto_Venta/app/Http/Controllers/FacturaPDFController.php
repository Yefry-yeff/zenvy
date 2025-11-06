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
            // Cargar la factura usando el modelo con la relación del usuario
            $factura = \App\Models\Factura::with('usuario')->find($facturaId);

            if (!$factura) {
                abort(404, 'Factura no encontrada');
            }

            // Cargar información del CAI asociado a la factura
            $cai = DB::table('cai')
                ->where('id', $factura->cai_id)
                ->first();
            $caiFacturaImpresa = $cai ? (array) $cai : null;

            // Cargar productos y servicios de forma unificada
            $productos = DB::table('factura_has_producto as fp')
                ->leftJoin('producto as p', 'fp.producto_id', '=', 'p.id')
                ->leftJoin('servicios as s', 'fp.Servicios_id', '=', 's.id')
                ->leftJoin('isv as i_producto', 'p.isv_id', '=', 'i_producto.id')
                ->leftJoin('isv as i_servicio', 's.isv_id', '=', 'i_servicio.id')
                ->leftJoin('unidad_medida as um', 'fp.unidad_medida_id', '=', 'um.id')
                ->where('fp.factura_id', $facturaId)
                ->select(
                    DB::raw('COALESCE(p.id, s.id) as producto_id'),
                    DB::raw('COALESCE(p.nombre, s.nombre) as nombre'),
                    DB::raw('COALESCE(p.codigo_barra, "SERVICIO") as codigo_barra'),
                    DB::raw('fp.isv_aplicado as tasa_isv'),
                    DB::raw('CASE WHEN p.id IS NOT NULL THEN "producto" ELSE "servicio" END as tipo'),
                    'fp.cantidad',
                    'fp.precio_unidad',
                    'fp.subtotal',
                    'fp.descuento',
                    'fp.isv_aplicado',
                    'fp.isv',
                    'fp.total',
                    'fp.indice', // IMPORTANTE: Agregar el índice
                    'fp.unidad_medida_id',
                    'um.nombre as unidad_nombre'
                )
                ->get()
                ->map(function($item) {
                    return (array) $item;
                })
                ->toArray();

            // Cargar TODOS los descuentos de la factura agrupados por producto, índice y tipo
            $descuentos = DB::table('descuentos')
                ->where('factura_id', $facturaId)
                ->select('producto_id', 'indice_factura_has_producto', 'Tipo_descuento', DB::raw('SUM(monto_total) as total_descuento'))
                ->groupBy('producto_id', 'indice_factura_has_producto', 'Tipo_descuento')
                ->get()
                ->groupBy(function($item) {
                    // Agrupar por producto_id + índice (clave compuesta)
                    return $item->producto_id . '_' . $item->indice_factura_has_producto;
                })
                ->map(function($descuentosProducto) {
                    return $descuentosProducto->mapWithKeys(function($item) {
                        return [$item->Tipo_descuento => $item->total_descuento];
                    });
                })
                ->toArray();

            // Agregar los descuentos agrupados a cada producto según su índice
            foreach ($productos as &$producto) {
                $productoId = $producto['producto_id'];
                $indice = $producto['indice'];
                $claveCompuesta = $productoId . '_' . $indice;

                $producto['descuentos'] = $descuentos[$claveCompuesta] ?? [];

                // Calcular el total de descuentos para este producto
                $producto['total_descuentos'] = array_sum($producto['descuentos']);
            }

            // Cargar métodos de pago
            $pagos = DB::table('factura_has_pago as fp')
                ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
                ->where('fp.factura_id', $facturaId)
                ->select('tp.nombre as metodo', 'fp.pago_recibido')
                ->get()
                ->map(function($item) {
                    return (array) $item;
                })
                ->toArray();

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
            // Cargar la factura usando el modelo con la relación del usuario
            $factura = \App\Models\Factura::with('usuario')->find($facturaId);

            if (!$factura) {
                abort(404, 'Factura no encontrada');
            }

            // Cargar información del CAI asociado a la factura
            $cai = DB::table('cai')
                ->where('id', $factura->cai_id)
                ->first();
            $caiFacturaImpresa = $cai ? (array) $cai : null;

            // Cargar productos y servicios de forma unificada
            $productos = DB::table('factura_has_producto as fp')
                ->leftJoin('producto as p', 'fp.producto_id', '=', 'p.id')
                ->leftJoin('servicios as s', 'fp.Servicios_id', '=', 's.id')
                ->leftJoin('isv as i_producto', 'p.isv_id', '=', 'i_producto.id')
                ->leftJoin('isv as i_servicio', 's.isv_id', '=', 'i_servicio.id')
                ->leftJoin('unidad_medida as um', 'fp.unidad_medida_id', '=', 'um.id')
                ->where('fp.factura_id', $facturaId)
                ->select(
                    DB::raw('COALESCE(p.id, s.id) as producto_id'),
                    DB::raw('COALESCE(p.nombre, s.nombre) as nombre'),
                    DB::raw('COALESCE(p.codigo_barra, "SERVICIO") as codigo_barra'),
                    DB::raw('COALESCE(i_producto.cantidad, i_servicio.cantidad, 0) as tasa_isv'),
                    DB::raw('CASE WHEN p.id IS NOT NULL THEN "producto" ELSE "servicio" END as tipo'),
                    'fp.cantidad',
                    'fp.precio_unidad',
                    'fp.subtotal',
                    'fp.descuento',
                    'fp.isv_aplicado',
                    'fp.isv',
                    'fp.total',
                    'fp.indice', // IMPORTANTE: Agregar el índice
                    'fp.unidad_medida_id',
                    'um.nombre as unidad_nombre'
                )
                ->get()
                ->map(function($item) {
                    return (array) $item;
                })
                ->toArray();

            // Cargar TODOS los descuentos de la factura agrupados por producto, índice y tipo
            $descuentos = DB::table('descuentos')
                ->where('factura_id', $facturaId)
                ->select('producto_id', 'indice_factura_has_producto', 'Tipo_descuento', DB::raw('SUM(monto_total) as total_descuento'))
                ->groupBy('producto_id', 'indice_factura_has_producto', 'Tipo_descuento')
                ->get()
                ->groupBy(function($item) {
                    // Agrupar por producto_id + índice (clave compuesta)
                    return $item->producto_id . '_' . $item->indice_factura_has_producto;
                })
                ->map(function($descuentosProducto) {
                    return $descuentosProducto->mapWithKeys(function($item) {
                        return [$item->Tipo_descuento => $item->total_descuento];
                    });
                })
                ->toArray();

            // Agregar los descuentos agrupados a cada producto según su índice
            foreach ($productos as &$producto) {
                $productoId = $producto['producto_id'];
                $indice = $producto['indice'];
                $claveCompuesta = $productoId . '_' . $indice;

                $producto['descuentos'] = $descuentos[$claveCompuesta] ?? [];

                // Calcular el total de descuentos para este producto
                $producto['total_descuentos'] = array_sum($producto['descuentos']);
            }

            // Cargar métodos de pago
            $pagos = DB::table('factura_has_pago as fp')
                ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
                ->where('fp.factura_id', $facturaId)
                ->select('tp.nombre as metodo', 'fp.pago_recibido')
                ->get()
                ->map(function($item) {
                    return (array) $item;
                })
                ->toArray();

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
