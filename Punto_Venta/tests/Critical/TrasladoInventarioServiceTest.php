<?php

namespace Tests\Critical;

use App\Models\Traslado;
use App\Models\User;
use App\Services\TrasladoInventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\CriticalDatabaseTestCase;

class TrasladoInventarioServiceTest extends CriticalDatabaseTestCase
{
    public function test_outgoing_transfer_rejects_invalid_quantities_and_duplicate_presentations(): void
    {
        $servicio = app(TrasladoInventarioService::class);
        $usuarioId = DB::table('users')->value('id');
        $trasladosAntes = Traslado::count();

        foreach ([0, -1] as $cantidad) {
            try {
                $servicio->crearSalida([
                    'bodega_origen_id' => 1,
                    'productos' => [[
                        'producto_id' => 1,
                        'precio_venta_id' => 1,
                        'cantidad' => $cantidad,
                    ]],
                ], $usuarioId);
                $this->fail("La cantidad {$cantidad} debió ser rechazada.");
            } catch (ValidationException) {
                $this->assertSame($trasladosAntes, Traslado::count());
            }
        }

        try {
            $servicio->crearSalida([
                'bodega_origen_id' => 1,
                'productos' => [
                    ['producto_id' => 1, 'precio_venta_id' => 1, 'cantidad' => 1],
                    ['producto_id' => 1, 'precio_venta_id' => 1, 'cantidad' => 1],
                ],
            ], $usuarioId);
            $this->fail('Las presentaciones duplicadas debieron ser rechazadas.');
        } catch (ValidationException) {
            $this->assertSame($trasladosAntes, Traslado::count());
        }
    }

    public function test_outgoing_transfer_rejects_a_unit_not_received_from_valencia(): void
    {
        $unidadesPermitidas = DB::table('id_zenvy_valencia as iz')
            ->join('compra_has_producto as chp', 'chp.compra_id', '=', 'iz.id_zenvy')
            ->where('iz.tipo_dato_migrado_id', 9)
            ->select('chp.producto_id', 'chp.unidad_medida_id')
            ->distinct();

        $candidate = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
            ->join('precio_has_venta as phv', 'phv.id', '=', 'rb.precio_venta_id')
            ->join('producto_valencia_zenvy as pvz', 'pvz.producto_id_zenvy', '=', 'rb.producto_id')
            ->leftJoinSub($unidadesPermitidas, 'permitida', function ($join): void {
                $join->on('permitida.producto_id', '=', 'rb.producto_id')
                    ->on('permitida.unidad_medida_id', '=', 'phv.unidad_medida_id');
            })
            ->where('b.cuenta_como_disponible', 1)
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->where('pvz.sincronizado', 1)
            ->whereNull('permitida.producto_id')
            ->select('b.id as bodega_id', 'rb.producto_id', 'rb.precio_venta_id')
            ->first();

        $this->assertNotNull($candidate, 'El clon necesita una presentación con stock cuya unidad no provenga de un traslado Valencia.');
        $this->expectException(ValidationException::class);

        app(TrasladoInventarioService::class)->crearSalida([
            'bodega_origen_id' => $candidate->bodega_id,
            'productos' => [[
                'producto_id' => $candidate->producto_id,
                'precio_venta_id' => $candidate->precio_venta_id,
                'cantidad' => 1,
            ]],
        ], DB::table('users')->value('id'));
    }

    public function test_product_search_only_returns_mapped_presentations_with_stock_in_selected_warehouse(): void
    {
        $usuario = User::firstOrFail();
        $bodegaId = DB::table('bodega')
            ->where('tienda_id', $usuario->tienda_id)
            ->where('estado_id', 1)
            ->where('cuenta_como_disponible', 1)
            ->where('principal', 1)
            ->value('id');

        $componente = Livewire::actingAs($usuario)
            ->test(\App\Livewire\Inventario\Traslados::class)
            ->set('bodegaOrigenId', (string) $bodegaId)
            ->call('abrirBusquedaProductos')
            ->assertSet('mostrarBusquedaProducto', true);

        $resultados = $componente->get('resultadosBusqueda');
        $this->assertNotEmpty($resultados, 'El clon necesita al menos una presentación disponible y mapeada con Valencia.');

        foreach ($resultados as $resultado) {
            $this->assertGreaterThan(0, (int) $resultado['stock_disponible']);
            $this->assertNotEmpty($resultado['producto_id_valencia']);
            $this->assertNotEmpty($resultado['unidad_medida_id_valencia']);
            $this->assertTrue(
                DB::table('producto_valencia_zenvy')
                    ->where('producto_id_zenvy', $resultado['producto_id'])
                    ->where('sincronizado', 1)
                    ->exists()
            );
            $this->assertTrue(
                DB::table('id_zenvy_valencia as iz')
                    ->join('compra_has_producto as chp', 'chp.compra_id', '=', 'iz.id_zenvy')
                    ->where('iz.tipo_dato_migrado_id', 9)
                    ->where('chp.producto_id', $resultado['producto_id'])
                    ->where('chp.unidad_medida_id', $resultado['unidad_medida_id_valencia'] === null
                        ? -1
                        : DB::table('id_zenvy_valencia')
                            ->where('tipo_dato_migrado_id', 5)
                            ->where('id_valencia', $resultado['unidad_medida_id_valencia'])
                            ->value('id_zenvy'))
                    ->exists()
            );
        }
    }

    public function test_outgoing_dispatch_deducts_fifo_stock_and_records_movements(): void
    {
        $candidate = DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
            ->join('producto_valencia_zenvy as pvz', 'pvz.producto_id_zenvy', '=', 'rb.producto_id')
            ->join('precio_has_venta as phv', 'phv.id', '=', 'rb.precio_venta_id')
            ->join('id_zenvy_valencia as umv', function ($join): void {
                $join->on('umv.id_zenvy', '=', 'phv.unidad_medida_id')
                    ->where('umv.tipo_dato_migrado_id', '=', 5);
            })
            ->join('compra_has_producto as chp', function ($join): void {
                $join->on('chp.producto_id', '=', 'rb.producto_id')
                    ->on('chp.unidad_medida_id', '=', 'phv.unidad_medida_id');
            })
            ->join('id_zenvy_valencia as iz', function ($join): void {
                $join->on('iz.id_zenvy', '=', 'chp.compra_id')
                    ->where('iz.tipo_dato_migrado_id', '=', 9);
            })
            ->where('b.cuenta_como_disponible', 1)
            ->where('rb.estado_id', 1)
            ->where('rb.cantidad_disponible', '>', 0)
            ->whereNotNull('rb.precio_venta_id')
            ->select('b.id as bodega_id', 'rb.producto_id', 'rb.precio_venta_id')
            ->distinct()
            ->first();

        $this->assertNotNull($candidate, 'El clon necesita stock de un producto sincronizado con Valencia.');

        $saldoAnterior = $this->stock(
            $candidate->bodega_id,
            $candidate->producto_id,
            $candidate->precio_venta_id
        );
        $usuarioId = DB::table('users')->value('id');
        $servicio = app(TrasladoInventarioService::class);

        $traslado = $servicio->crearSalida([
            'bodega_origen_id' => $candidate->bodega_id,
            'comentario' => 'Prueba automatizada',
            'productos' => [[
                'producto_id' => $candidate->producto_id,
                'precio_venta_id' => $candidate->precio_venta_id,
                'cantidad' => 1,
            ]],
        ], $usuarioId);

        $despachado = $servicio->despachar($traslado->id, $usuarioId);

        $this->assertSame('en_transito', $despachado->estado);
        $this->assertSame(
            $saldoAnterior - 1,
            $this->stock($candidate->bodega_id, $candidate->producto_id, $candidate->precio_venta_id)
        );
        $this->assertDatabaseHas('movimiento_inventario', [
            'traslado_id' => $traslado->id,
            'tipo' => 'salida_traslado',
            'cantidad' => -1,
        ]);
        $this->assertDatabaseHas('traslado_lote', [
            'traslado_detalle_id' => $traslado->detalles->first()->id,
            'cantidad' => 1,
        ]);

        $recepcionUuid = (string) Str::uuid();
        $confirmacion = [[
            'detalle_id_zenvy' => $despachado->detalles->first()->id,
            'producto_id_valencia' => $despachado->detalles->first()->producto_externo_id,
            'cantidad_recibida' => 1,
            'cantidad_rechazada' => 0,
        ]];
        $servicio->confirmarRecepcionValencia($traslado->uuid, $recepcionUuid, $confirmacion);
        $repetido = $servicio->confirmarRecepcionValencia($traslado->uuid, $recepcionUuid, $confirmacion);

        $this->assertSame('recibido', $repetido->estado);
        $this->assertSame(1, $repetido->detalles->first()->cantidad_recibida);
        $this->assertContains($recepcionUuid, $repetido->metadata['recepciones_valencia']);
    }

    public function test_incoming_registration_is_idempotent_and_receipt_creates_available_stock(): void
    {
        $mapping = DB::table('producto_valencia_zenvy as pvz')
            ->join('precio_has_venta as phv', 'phv.producto_id', '=', 'pvz.producto_id_zenvy')
            ->where('pvz.sincronizado', 1)
            ->where('phv.estado_id', 1)
            ->select('pvz.producto_id_valencia', 'pvz.producto_id_zenvy', 'phv.id as precio_venta_id')
            ->first();
        $bodega = DB::table('bodega')
            ->where('estado_id', 1)
            ->where('cuenta_como_disponible', 1)
            ->first();
        $seccionId = DB::table('seccion as s')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->where('sg.bodega_id', $bodega->id)
            ->where('s.estado_id', 1)
            ->value('s.id');

        $this->assertNotNull($mapping);
        $this->assertNotNull($seccionId);

        $uuid = (string) Str::uuid();
        $payload = [
            'uuid' => $uuid,
            'referencia_externa' => 'TEST-' . Str::random(12),
            'bodega_destino_id' => $bodega->id,
            'fecha_despacho' => now()->toIso8601String(),
            'productos' => [[
                'producto_id_valencia' => $mapping->producto_id_valencia,
                'cantidad' => 2,
            ]],
        ];
        $servicio = app(TrasladoInventarioService::class);

        $primero = $servicio->registrarEntradaValencia($payload);
        $segundo = $servicio->registrarEntradaValencia($payload);

        $this->assertSame($primero->id, $segundo->id);
        $this->assertSame(1, Traslado::where('uuid', $uuid)->count());

        $detalle = $primero->detalles->first();
        $usuarioId = DB::table('users')->value('id');
        $recibido = $servicio->recibirEntrada($primero->id, [[
            'detalle_id' => $detalle->id,
            'cantidad_recibida' => 2,
            'cantidad_rechazada' => 0,
            'seccion_id' => $seccionId,
        ]], $usuarioId);

        $this->assertSame('recibido', $recibido->estado);
        $this->assertDatabaseHas('recibido_bodega', [
            'producto_id' => $mapping->producto_id_zenvy,
            'seccion_id' => $seccionId,
            'cantidad_disponible' => 2,
        ]);
        $this->assertDatabaseHas('movimiento_inventario', [
            'traslado_id' => $primero->id,
            'tipo' => 'entrada_traslado',
            'cantidad' => 2,
        ]);
    }

    private function stock(int $bodegaId, int $productoId, int $precioVentaId): int
    {
        return (int) DB::table('recibido_bodega as rb')
            ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
            ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
            ->where('sg.bodega_id', $bodegaId)
            ->where('rb.producto_id', $productoId)
            ->where('rb.precio_venta_id', $precioVentaId)
            ->where('rb.estado_id', 1)
            ->sum('rb.cantidad_disponible');
    }
}
