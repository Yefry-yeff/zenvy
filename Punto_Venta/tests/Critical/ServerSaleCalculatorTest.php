<?php

namespace Tests\Critical;

use App\Services\Api\ServerSaleCalculator;
use Illuminate\Support\Facades\DB;
use Tests\Support\CriticalDatabaseTestCase;

class ServerSaleCalculatorTest extends CriticalDatabaseTestCase
{
    public function test_catalog_price_and_tax_override_client_amounts(): void
    {
        $catalog = DB::table('precio_has_venta as phv')
            ->join('producto as p', 'p.id', '=', 'phv.producto_id')
            ->join('isv as i', 'i.id', '=', 'p.isv_id')
            ->where('phv.estado_id', 1)
            ->where('p.estado_id', 1)
            ->select('phv.id', 'phv.producto_id', 'phv.precio', 'i.cantidad as tax_rate')
            ->first();
        $this->assertNotNull($catalog);

        $result = app(ServerSaleCalculator::class)->calculate([
            [
                'product_id' => $catalog->producto_id,
                'price_id' => $catalog->id,
                'quantity' => 2,
                'price' => 0.01,
                'discount' => 10,
            ],
        ], 5.00);

        $gross = round((float) $catalog->precio * 2, 2);
        $discount = round($gross * 0.10, 2);
        $subtotal = round($gross - $discount, 2);
        $tax = round($subtotal * ((float) $catalog->tax_rate / 100), 2);

        $this->assertSame((float) $catalog->precio, $result['items'][0]['price']);
        $this->assertSame($catalog->id, $result['items'][0]['price_id']);
        $this->assertSame($subtotal, $result['subtotal']);
        $this->assertSame($discount, $result['discount']);
        $this->assertSame($tax, $result['tax']);
        $this->assertSame(round($subtotal + $tax + 5.00, 2), $result['total']);
    }
}