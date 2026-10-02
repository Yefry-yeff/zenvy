<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use LogicException;

class InventoryRestorationService
{
    public function restore(int $invoiceId, int $userId): array
    {
        $details = DB::table('detalle_factura_lote')
            ->where('factura_id', $invoiceId)
            ->whereNull('restored_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($details->isEmpty()) {
            throw new LogicException(
                'La factura no tiene lotes pendientes de restaurar. Para facturas historicas, anule sin afectar inventario.'
            );
        }

        $lots = DB::table('recibido_bodega')
            ->whereIn('id', $details->pluck('recibido_bodega_id')->unique())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($lots->count() !== $details->pluck('recibido_bodega_id')->unique()->count()) {
            throw new LogicException('Uno o mas lotes originales de la factura ya no existen.');
        }

        $restored = [];

        foreach ($details->groupBy('recibido_bodega_id') as $lotId => $lotDetails) {
            $quantity = (int) $lotDetails->sum('cantidad_usada');
            $lot = $lots->get($lotId);

            DB::table('recibido_bodega')->where('id', $lotId)->update([
                'cantidad_disponible' => (int) $lot->cantidad_disponible + $quantity,
                'estado_id' => 1,
                'updated_at' => now(),
            ]);

            DB::table('detalle_factura_lote')
                ->whereIn('id', $lotDetails->pluck('id'))
                ->update([
                    'restored_at' => now(),
                    'restored_by' => $userId,
                    'updated_at' => now(),
                ]);

            $unitPrice = (float) ($lotDetails->first()->precio_unitario ?? 0);
            $productId = (int) $lotDetails->first()->producto_id;
            $restored[] = [
                'lot_id' => (int) $lotId,
                'recibido_bodega_id' => (int) $lotId,
                'product_id' => $productId,
                'producto_id' => $productId,
                'quantity' => $quantity,
                'cantidad' => $quantity,
                'precio_unidad' => $unitPrice,
                'subtotal' => round($quantity * $unitPrice, 2),
            ];
        }

        return $restored;
    }
}