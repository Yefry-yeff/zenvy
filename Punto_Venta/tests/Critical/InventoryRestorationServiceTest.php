<?php

namespace Tests\Critical;

use App\Services\InventoryAllocationService;
use App\Services\InventoryRestorationService;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Support\CriticalDatabaseTestCase;

class InventoryRestorationServiceTest extends CriticalDatabaseTestCase
{
    public function test_cancellation_restores_each_exact_lot_only_once(): void
    {
        $candidate = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 1)
            ->where('b.principal', 1)
            ->whereNotNull('rb.precio_venta_id')
            ->select(
                'rb.id as lot_id',
                'rb.producto_id',
                'rb.precio_venta_id',
                'rb.cantidad_disponible',
                'b.tienda_id'
            )
            ->first();
        $this->assertNotNull($candidate);

        $invoiceId = DB::table('factura')->value('id');
        $userId = DB::table('users')->value('id');

        app(InventoryAllocationService::class)->allocate(
            $invoiceId,
            $candidate->producto_id,
            $candidate->precio_venta_id,
            $candidate->tienda_id,
            2,
            10.00
        );

        $restored = app(InventoryRestorationService::class)->restore($invoiceId, $userId);

        $this->assertSame(2, array_sum(array_column($restored, 'quantity')));
        $this->assertSame(
            $candidate->cantidad_disponible,
            DB::table('recibido_bodega')->where('id', $candidate->lot_id)->value('cantidad_disponible')
        );
        $this->assertSame(
            0,
            DB::table('detalle_factura_lote')
                ->where('factura_id', $invoiceId)
                ->whereNull('restored_at')
                ->count()
        );

        $this->expectException(LogicException::class);
        app(InventoryRestorationService::class)->restore($invoiceId, $userId);
    }
}