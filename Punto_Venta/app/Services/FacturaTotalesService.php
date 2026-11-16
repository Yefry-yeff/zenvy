<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FacturaTotalesService
{
    public function descuentosTotalesPorProducto(Collection $productoIds): Collection
    {
        $productoIds = $productoIds->filter()->unique()->values();
        $totales = $productoIds->mapWithKeys(fn ($productoId) => [$productoId => 0.0]);

        if ($productoIds->isEmpty()) {
            return $totales;
        }

        $facturas = DB::table('factura as f')
            ->join('factura_has_producto as fp', 'f.id', '=', 'fp.factura_id')
            ->where('f.estado_factura_id', 1)
            ->whereIn('fp.producto_id', $productoIds)
            ->select('f.id', 'f.monto_descuento')
            ->distinct()
            ->get();

        if ($facturas->isEmpty()) {
            return $totales;
        }

        $facturaIds = $facturas->pluck('id');
        $lineasPorFactura = DB::table('factura_has_producto')
            ->whereIn('factura_id', $facturaIds)
            ->select('factura_id', 'producto_id', 'indice', 'cantidad', 'precio_unidad')
            ->get()
            ->groupBy('factura_id');

        $descuentos = DB::table('descuentos')
            ->whereIn('factura_id', $facturaIds)
            ->select(
                'factura_id',
                'producto_id',
                'indice_factura_has_producto',
                DB::raw('SUM(monto_total) as total_descuento')
            )
            ->groupBy('factura_id', 'producto_id', 'indice_factura_has_producto')
            ->get()
            ->mapWithKeys(fn ($descuento) => [
                $this->claveLinea(
                    $descuento->factura_id,
                    $descuento->producto_id,
                    $descuento->indice_factura_has_producto
                ) => (float) $descuento->total_descuento,
            ]);

        foreach ($facturas as $factura) {
            $lineas = $lineasPorFactura->get($factura->id, collect())->values();
            $bases = [];

            foreach ($lineas as $index => $linea) {
                $importe = (float) $linea->cantidad * (float) $linea->precio_unidad;
                $clave = $this->claveLinea($factura->id, $linea->producto_id, $linea->indice);
                $linea->descuento_producto = $descuentos->get($clave, 0);
                $bases[$index] = max(0, $importe - $linea->descuento_producto);
            }

            $distribucion = $this->distribuirMontoProporcional(
                (float) ($factura->monto_descuento ?? 0),
                $bases
            );

            foreach ($lineas as $index => $linea) {
                if (!$totales->has($linea->producto_id)) {
                    continue;
                }

                $totales[$linea->producto_id] +=
                    $linea->descuento_producto + ($distribucion[$index] ?? 0);
            }
        }

        return $totales->map(fn ($total) => round($total, 2));
    }

    public function aplicar(Collection $facturas): Collection
    {
        if ($facturas->isEmpty()) {
            return $facturas;
        }

        $facturaIds = $facturas->pluck('id');
        $lineasPorFactura = DB::table('factura_has_producto')
            ->whereIn('factura_id', $facturaIds)
            ->select(
                'factura_id',
                'producto_id',
                'indice',
                'cantidad',
                'precio_unidad',
                'isv_aplicado as tasa_isv'
            )
            ->get()
            ->groupBy('factura_id');

        $descuentos = DB::table('descuentos')
            ->whereIn('factura_id', $facturaIds)
            ->select(
                'factura_id',
                'producto_id',
                'indice_factura_has_producto',
                DB::raw('SUM(monto_total) as total_descuento')
            )
            ->groupBy('factura_id', 'producto_id', 'indice_factura_has_producto')
            ->get()
            ->mapWithKeys(fn ($descuento) => [
                $this->claveLinea(
                    $descuento->factura_id,
                    $descuento->producto_id,
                    $descuento->indice_factura_has_producto
                ) => (float) $descuento->total_descuento,
            ]);

        return $facturas->each(function ($factura) use ($lineasPorFactura, $descuentos) {
            $lineas = $lineasPorFactura->get($factura->id, collect())->values();
            $bases = [];

            foreach ($lineas as $index => $linea) {
                $importe = (float) $linea->cantidad * (float) $linea->precio_unidad;
                $linea->subtotal_bruto = $importe;
                $clave = $this->claveLinea($factura->id, $linea->producto_id, $linea->indice);
                $linea->descuento_producto = $descuentos->get($clave, 0);
                $bases[$index] = max(0, $importe - $linea->descuento_producto);
            }

            $distribucion = $this->distribuirMontoProporcional(
                (float) ($factura->monto_descuento ?? 0),
                $bases
            );

            foreach ($lineas as $index => $linea) {
                $linea->descuento_total = $linea->descuento_producto + ($distribucion[$index] ?? 0);
                $linea->base_neta = max(
                    0,
                    ((float) $linea->cantidad * (float) $linea->precio_unidad) - $linea->descuento_total
                );
            }

            $subtotalGravado15 = $lineas
                ->filter(fn ($linea) => (float) $linea->tasa_isv === 15.0)
                ->sum('subtotal_bruto');
            $subtotalGravado18 = $lineas
                ->filter(fn ($linea) => (float) $linea->tasa_isv === 18.0)
                ->sum('subtotal_bruto');
            $subtotalExento = $lineas
                ->filter(fn ($linea) => (float) $linea->tasa_isv === 0.0)
                ->sum('subtotal_bruto');
            $gravado15 = $lineas->filter(fn ($linea) => (float) $linea->tasa_isv === 15.0)->sum('base_neta');
            $gravado18 = $lineas->filter(fn ($linea) => (float) $linea->tasa_isv === 18.0)->sum('base_neta');
            $isv15 = $lineas->filter(fn ($linea) => (float) $linea->tasa_isv === 15.0)->sum(
                fn ($linea) => round($linea->base_neta * 0.15, 2)
            );
            $isv18 = $lineas->filter(fn ($linea) => (float) $linea->tasa_isv === 18.0)->sum(
                fn ($linea) => round($linea->base_neta * 0.18, 2)
            );

            $factura->setAttribute('reporte_subtotal_gravado_15', round($subtotalGravado15, 2));
            $factura->setAttribute('reporte_subtotal_gravado_18', round($subtotalGravado18, 2));
            $factura->setAttribute('reporte_subtotal_gravado', round($subtotalGravado15 + $subtotalGravado18, 2));
            $factura->setAttribute('reporte_subtotal_exento', round($subtotalExento, 2));
            $factura->setAttribute('reporte_subtotal', round($subtotalGravado15 + $subtotalGravado18 + $subtotalExento, 2));
            $factura->setAttribute('reporte_gravado_15', round($gravado15, 2));
            $factura->setAttribute('reporte_gravado_18', round($gravado18, 2));
            $factura->setAttribute('reporte_gravado', round($gravado15 + $gravado18, 2));
            $factura->setAttribute('reporte_exento', round(
                $lineas->filter(fn ($linea) => (float) $linea->tasa_isv === 0.0)->sum('base_neta'),
                2
            ));
            $descuentoGravado = $lineas
                ->filter(fn ($linea) => (float) $linea->tasa_isv > 0)
                ->sum('descuento_total');
            $descuentoExento = $lineas
                ->filter(fn ($linea) => (float) $linea->tasa_isv === 0.0)
                ->sum('descuento_total');
            $factura->setAttribute('reporte_descuento_gravado', round($descuentoGravado, 2));
            $factura->setAttribute('reporte_descuento_exento', round($descuentoExento, 2));
            $factura->setAttribute('reporte_descuento', round($descuentoGravado + $descuentoExento, 2));
            $factura->setAttribute('reporte_isv_15', round($isv15, 2));
            $factura->setAttribute('reporte_isv_18', round($isv18, 2));
            $factura->setAttribute('reporte_isv', round($isv15 + $isv18, 2));

            return $factura;
        });
    }

    public function sumar(Collection $facturas): object
    {
        return (object) [
            'total_subtotal_gravado' => $facturas->sum('reporte_subtotal_gravado'),
            'total_subtotal_exento' => $facturas->sum('reporte_subtotal_exento'),
            'total_gravado' => $facturas->sum('reporte_gravado'),
            'total_exento' => $facturas->sum('reporte_exento'),
            'total_descuento_gravado' => $facturas->sum('reporte_descuento_gravado'),
            'total_descuento_exento' => $facturas->sum('reporte_descuento_exento'),
            'total_descuento' => $facturas->sum('reporte_descuento'),
            'total_subtotal' => $facturas->sum(fn ($factura) =>
                (float) $factura->reporte_gravado + (float) $factura->reporte_exento
            ),
            'total_isv' => $facturas->sum('reporte_isv'),
            'total_total' => $facturas->sum('total'),
        ];
    }

    private function claveLinea($facturaId, $productoId, $indice): string
    {
        return $facturaId . '_' . ($productoId ?? 0) . '_' . ($indice ?? 0);
    }

    private function distribuirMontoProporcional(float $monto, array $bases): array
    {
        $centavos = (int) round($monto * 100);
        $totalBase = array_sum($bases);

        if ($centavos <= 0 || $totalBase <= 0) {
            return array_fill_keys(array_keys($bases), 0);
        }

        $distribucion = [];
        $residuos = [];
        $asignados = 0;

        foreach ($bases as $index => $base) {
            $exacto = $centavos * ($base / $totalBase);
            $entero = (int) floor($exacto);
            $distribucion[$index] = $entero;
            $residuos[$index] = $exacto - $entero;
            $asignados += $entero;
        }

        arsort($residuos, SORT_NUMERIC);
        foreach (array_keys($residuos) as $index) {
            if ($asignados >= $centavos) {
                break;
            }
            $distribucion[$index]++;
            $asignados++;
        }

        return array_map(fn ($valor) => $valor / 100, $distribucion);
    }
}