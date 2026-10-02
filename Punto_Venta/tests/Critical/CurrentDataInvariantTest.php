<?php

namespace Tests\Critical;

use Illuminate\Support\Facades\DB;
use Tests\Support\CriticalDatabaseTestCase;

class CurrentDataInvariantTest extends CriticalDatabaseTestCase
{
    public function test_critical_data_invariants_hold_on_the_isolated_clone(): void
    {
        $this->assertSame(0, DB::table('recibido_bodega')->where('cantidad_disponible', '<', 0)->count());

        $duplicateInvoices = DB::table('factura')
            ->select('numero_factura')
            ->groupBy('numero_factura')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $duplicateInvoices);

        $duplicateCancellations = DB::table('facturas_anuladas')
            ->select('factura_id')
            ->groupBy('factura_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $duplicateCancellations);

        $cancelledWithoutAudit = DB::table('factura as f')
            ->leftJoin('facturas_anuladas as fa', 'fa.factura_id', '=', 'f.id')
            ->where('f.estado_factura_id', 2)
            ->whereNull('fa.id')
            ->count();
        $this->assertSame(0, $cancelledWithoutAudit);

        $orphanLines = DB::table('factura_has_producto as fp')
            ->leftJoin('factura as f', 'f.id', '=', 'fp.factura_id')
            ->whereNull('f.id')
            ->count();
        $this->assertSame(0, $orphanLines);
    }
}