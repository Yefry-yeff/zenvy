<?php

namespace Tests\Critical;

use App\Services\InventoryAllocationService;
use Illuminate\Support\Facades\DB;
use Tests\Support\CriticalDatabaseTestCase;

class InventoryAllocationServiceTest extends CriticalDatabaseTestCase
{
    public function test_fifo_allocation_locks_and_records_the_exact_lots_used(): void
    {
        $candidate = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->where('b.principal', 1)
            ->whereNotNull('rb.precio_venta_id')
            ->select('rb.producto_id', 'rb.precio_venta_id', 'b.tienda_id')
            ->groupBy('rb.producto_id', 'rb.precio_venta_id', 'b.tienda_id')
            ->havingRaw('COUNT(*) >= 2')
            ->havingRaw('SUM(rb.cantidad_disponible) >= 2')
            ->first();
        $this->assertNotNull($candidate, 'El clon necesita un producto con al menos dos lotes FIFO activos.');

        $lots = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
            ->where('rb.producto_id', $candidate->producto_id)
            ->where('rb.precio_venta_id', $candidate->precio_venta_id)
            ->where('b.tienda_id', $candidate->tienda_id)
            ->where('b.principal', 1)
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->orderBy('rb.fecha_recibido')
            ->orderBy('rb.id')
            ->select('rb.id', 'rb.cantidad_disponible')
            ->get();

        $invoiceId = DB::table('factura')->value('id');
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $allocations = app(InventoryAllocationService::class)->allocate(
            $invoiceId,
            $candidate->producto_id,
            $candidate->precio_venta_id,
            $candidate->tienda_id,
            2,
            10.00
        );

        $this->assertSame(2, array_sum(array_column($allocations, 'quantity')));
        $this->assertSame($lots->first()->id, $allocations[0]['lot_id']);
        $this->assertSame(
            count($allocations),
            DB::table('detalle_factura_lote')->where('factura_id', $invoiceId)->count()
        );
        $this->assertTrue(
            collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'for update')),
            'La seleccion FIFO debe bloquear los lotes con FOR UPDATE.'
        );
    }
}