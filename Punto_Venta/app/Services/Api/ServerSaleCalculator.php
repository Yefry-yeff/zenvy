<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ServerSaleCalculator
{
    public function calculate(array $items, float $shippingCost = 0): array
    {
        $calculatedItems = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $catalog = $this->resolveCatalogPrice($item);
            $quantity = (int) $item['quantity'];
            $discountPercent = (float) ($item['discount'] ?? 0);
            $price = (float) $catalog->price;
            $gross = round($price * $quantity, 2);
            $discount = round($gross * ($discountPercent / 100), 2);
            $itemSubtotal = round($gross - $discount, 2);
            $tax = round($itemSubtotal * ((float) $catalog->tax_rate / 100), 2);
            $total = round($itemSubtotal + $tax, 2);

            $calculatedItems[] = [
                'product_id' => (int) $catalog->product_id,
                'price_id' => (int) $catalog->price_id,
                'unit_id' => (int) $catalog->unit_id,
                'quantity' => $quantity,
                'price' => $price,
                'discount' => $discountPercent,
                'discount_amount' => $discount,
                'tax_rate' => (float) $catalog->tax_rate,
                'subtotal' => $itemSubtotal,
                'tax' => $tax,
                'total' => $total,
            ];

            $subtotal += $itemSubtotal;
            $discountTotal += $discount;
            $taxTotal += $tax;
        }

        $subtotal = round($subtotal, 2);
        $discountTotal = round($discountTotal, 2);
        $taxTotal = round($taxTotal, 2);
        $shippingCost = round(max(0, $shippingCost), 2);

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'discount' => $discountTotal,
            'tax' => $taxTotal,
            'shipping_cost' => $shippingCost,
            'total' => round($subtotal + $taxTotal + $shippingCost, 2),
        ];
    }

    private function resolveCatalogPrice(array $item): object
    {
        $query = DB::table('precio_has_venta as phv')
            ->join('producto as p', 'p.id', '=', 'phv.producto_id')
            ->join('isv as i', 'i.id', '=', 'p.isv_id')
            ->where('phv.producto_id', $item['product_id'])
            ->where('phv.estado_id', 1)
            ->where('p.estado_id', 1)
            ->select(
                'phv.id as price_id',
                'phv.producto_id as product_id',
                'phv.unidad_medida_id as unit_id',
                'phv.precio as price',
                'i.cantidad as tax_rate'
            );

        if (!empty($item['price_id'])) {
            $catalog = $query->where('phv.id', $item['price_id'])->first();
        } else {
            $matches = $query->where('phv.precio', $item['price'])->limit(2)->get();
            $catalog = $matches->count() === 1 ? $matches->first() : null;
        }

        if (!$catalog) {
            throw new InvalidArgumentException(
                "No se pudo identificar una presentacion activa para el producto {$item['product_id']}. Envie price_id."
            );
        }

        return $catalog;
    }
}