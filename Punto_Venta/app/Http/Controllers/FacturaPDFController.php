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
                ->toArray();

            // Convertir cada objeto stdClass a array asociativo
            $productos = array_map(function($item) {
                return json_decode(json_encode($item), true);
            }, $productos);

            $productos = $this->aplicarDescuentosPorLinea(
                $productos,
                $facturaId,
                (float) ($factura->monto_descuento ?? 0)
            );

            // Cargar métodos de pago
            $pagos = DB::table('factura_has_pago as fp')
                ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
                ->where('fp.factura_id', $facturaId)
                ->select('tp.nombre as metodo', 'fp.pago_recibido')
                ->get()
                ->toArray();

            // Convertir cada objeto a array asociativo
            $pagos = array_map(function($item) {
                return json_decode(json_encode($item), true);
            }, $pagos);

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
                ->leftJoin('precio_has_venta as phv', 'fp.precio_id', '=', 'phv.id')
                ->leftJoin('isv as i_producto', 'p.isv_id', '=', 'i_producto.id')
                ->leftJoin('isv as i_servicio', 's.isv_id', '=', 'i_servicio.id')
                ->leftJoin('unidad_medida as um', 'fp.unidad_medida_id', '=', 'um.id')
                ->where('fp.factura_id', $facturaId)
                ->select(
                    DB::raw('COALESCE(p.id, s.id) as producto_id'),
                    DB::raw('CASE 
                        WHEN p.id IS NOT NULL AND phv.descripcion IS NOT NULL AND phv.descripcion != "" 
                        THEN CONCAT(p.nombre, " - ", phv.descripcion) 
                        ELSE COALESCE(p.nombre, s.nombre) 
                    END as nombre'),
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
                ->toArray();

            // Convertir cada objeto stdClass a array asociativo
            $productos = array_map(function($item) {
                return json_decode(json_encode($item), true);
            }, $productos);

            $productos = $this->aplicarDescuentosPorLinea(
                $productos,
                $facturaId,
                (float) ($factura->monto_descuento ?? 0)
            );
            
            // DEBUG: Log de la estructura de productos
            Log::info('DEBUG Productos para PDF (previsualizar):', [
                'factura_id' => $facturaId,
                'total_productos' => count($productos),
                'indices' => array_keys($productos),
                'estructura_completa' => $productos
            ]);

            // Cargar métodos de pago
            $pagos = DB::table('factura_has_pago as fp')
                ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
                ->where('fp.factura_id', $facturaId)
                ->select('tp.nombre as metodo', 'fp.pago_recibido')
                ->get()
                ->toArray();

            // Convertir cada objeto a array asociativo
            $pagos = array_map(function($item) {
                return json_decode(json_encode($item), true);
            }, $pagos);

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

    private function aplicarDescuentosPorLinea(array $productos, int $facturaId, float $descuentoSubtotal): array
    {
        $descuentos = DB::table('descuentos')
            ->where('factura_id', $facturaId)
            ->select(
                'producto_id',
                'indice_factura_has_producto',
                'Tipo_descuento',
                DB::raw('SUM(monto_total) as total_descuento')
            )
            ->groupBy('producto_id', 'indice_factura_has_producto', 'Tipo_descuento')
            ->get()
            ->groupBy(fn ($item) => $item->producto_id . '_' . ($item->indice_factura_has_producto ?? 0))
            ->map(fn ($items) => $items->mapWithKeys(
                fn ($item) => [$item->Tipo_descuento => (float) $item->total_descuento]
            ))
            ->toArray();

        $bases = [];
        foreach ($productos as $index => &$producto) {
            $clave = ($producto['producto_id'] ?? 0) . '_' . ($producto['indice'] ?? 0);
            $producto['descuentos'] = $descuentos[$clave] ?? [];
            $descuentoProducto = array_sum($producto['descuentos']);
            $importe = (float) ($producto['cantidad'] ?? 0) * (float) ($producto['precio_unidad'] ?? 0);
            $bases[$index] = max(0, $importe - $descuentoProducto);
        }
        unset($producto);

        $distribucion = $this->distribuirMontoProporcional($descuentoSubtotal, $bases);

        foreach ($productos as $index => &$producto) {
            $descuentoDistribuido = $distribucion[$index] ?? 0;
            if ($descuentoDistribuido > 0) {
                $producto['descuentos']['Subtotal'] =
                    ($producto['descuentos']['Subtotal'] ?? 0) + $descuentoDistribuido;
            }

            $producto['total_descuentos'] = round(array_sum($producto['descuentos']), 2);
            $cantidad = (float) ($producto['cantidad'] ?? 0);
            $producto['descuento_por_unidad'] = $cantidad > 0
                ? $producto['total_descuentos'] / $cantidad
                : 0;
        }
        unset($producto);

        return array_values($productos);
    }

    private function distribuirMontoProporcional(float $monto, array $bases): array
    {
        $centavos = (int) round($monto * 100);
        $totalBase = array_sum($bases);

        if ($centavos <= 0 || $totalBase <= 0) {
            return array_fill_keys(array_keys($bases), 0);
        }

        $distribucionCentavos = [];
        $residuos = [];
        $asignados = 0;

        foreach ($bases as $index => $base) {
            $exacto = $centavos * ($base / $totalBase);
            $entero = (int) floor($exacto);
            $distribucionCentavos[$index] = $entero;
            $residuos[$index] = $exacto - $entero;
            $asignados += $entero;
        }

        arsort($residuos, SORT_NUMERIC);
        foreach (array_keys($residuos) as $index) {
            if ($asignados >= $centavos) {
                break;
            }
            $distribucionCentavos[$index]++;
            $asignados++;
        }

        return array_map(fn ($valor) => $valor / 100, $distribucionCentavos);
    }
}
