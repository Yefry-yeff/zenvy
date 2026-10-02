<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryAllocationService
{
    public function allocate(
        int $invoiceId,
        int $productId,
        int $priceId,
        int $storeId,
        int $quantity,
        float $unitPrice
    ): array {
        if ($quantity <= 0) {
            throw new RuntimeException('La cantidad a descontar debe ser mayor que cero.');
        }

        $lots = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
            ->where('b.tienda_id', $storeId)
            ->where('b.principal', 1)
            ->where('rb.producto_id', $productId)
            ->where('rb.precio_venta_id', $priceId)
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->orderBy('rb.fecha_recibido')
            ->orderBy('rb.id')
            ->select('rb.id', 'rb.cantidad_disponible')
            ->lockForUpdate()
            ->get();

        if ($lots->sum('cantidad_disponible') < $quantity) {
            throw new RuntimeException("Stock insuficiente para el producto ID: {$productId}");
        }

        $remaining = $quantity;
        $allocations = [];

        foreach ($lots as $lot) {
            if ($remaining === 0) {
                break;
            }

            $allocated = min($remaining, (int) $lot->cantidad_disponible);
            $newQuantity = (int) $lot->cantidad_disponible - $allocated;

            DB::table('recibido_bodega')->where('id', $lot->id)->update([
                'cantidad_disponible' => $newQuantity,
                'estado_id' => $newQuantity === 0 ? 2 : 1,
                'updated_at' => now(),
            ]);

            DB::table('detalle_factura_lote')->insert([
                'factura_id' => $invoiceId,
                'recibido_bodega_id' => $lot->id,
                'producto_id' => $productId,
                'cantidad_usada' => $allocated,
                'precio_unitario' => $unitPrice,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $allocations[] = [
                'lot_id' => $lot->id,
                'quantity' => $allocated,
            ];
            $remaining -= $allocated;
        }

        return $allocations;
    }
}