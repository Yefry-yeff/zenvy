<?php

namespace Tests\Critical;

use App\Services\CAIService;
use Illuminate\Support\Facades\DB;
use Tests\Support\CriticalDatabaseTestCase;

class CAIServiceTest extends CriticalDatabaseTestCase
{
    public function test_next_invoice_number_is_consumed_under_a_database_lock(): void
    {
        $gestion = DB::table('gestion_cai')->first();
        $this->assertNotNull($gestion);

        DB::table('cai')->where('id', $gestion->cai_id)->update([
            'estado_id' => 1,
            'fecha_limite_emision' => '2099-12-31',
        ]);
        DB::table('gestion_cai')->where('id', $gestion->id)->update([
            'numero_actual' => 100,
            'cantidad_no_utilizada' => 2,
            'estado_id' => 1,
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $result = app(CAIService::class)->obtenerSiguienteNumeroFactura();
        $updated = DB::table('gestion_cai')->where('id', $gestion->id)->first();

        $this->assertSame(100, $result['numero_secuencia']);
        $this->assertSame(101, $updated->numero_actual);
        $this->assertSame(1, $updated->cantidad_no_utilizada);
        $this->assertTrue(
            collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'for update')),
            'La seleccion del correlativo CAI debe bloquear la fila con FOR UPDATE.'
        );
    }
}