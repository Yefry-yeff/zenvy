<?php

namespace Tests\Critical;

use App\Livewire\SalaDeVentas\Ventas;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\CriticalDatabaseTestCase;

class ProductSearchPerformanceTest extends CriticalDatabaseTestCase
{
    public function test_barcode_search_uses_grouped_queries_and_preserves_stock(): void
    {
        Auth::login(User::query()->firstOrFail());
        $component = Livewire::test(Ventas::class);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $component->set('busquedaProductosServicios', '0801');

        $resultados = $component->get('resultadosBusqueda');
        $this->assertNotEmpty($resultados);
        $this->assertLessThanOrEqual(15, count(DB::getQueryLog()));

        foreach ($resultados as $resultado) {
            if ($resultado->precio_id) {
                $stockEsperado = $component->instance()->calcularStockTotalPorUnidad(
                    $resultado->id,
                    $resultado->unidad_medida_id,
                    $resultado->precio_id
                );
            } else {
                $stockEsperado = $component->instance()->obtenerStockTotal($resultado->id);
            }

            $this->assertSame((float) $stockEsperado, (float) $resultado->stock_total_unidad);
        }
    }

    public function test_unfiltered_search_keeps_first_hundred_results_paginated(): void
    {
        Auth::login(User::query()->firstOrFail());
        $component = Livewire::test(Ventas::class)->call('buscarProductos');

        $primeraPagina = $component->get('resultadosBusqueda')->pluck('precio_id')->all();

        $component->call('paginaSiguienteBusqueda');

        $segundaPagina = $component->get('resultadosBusqueda')->pluck('precio_id')->all();

        $this->assertSame(100, $component->get('totalResultadosBusqueda'));
        $this->assertCount(24, $primeraPagina);
        $this->assertCount(24, $segundaPagina);
        $this->assertSame(2, $component->get('paginaResultadosBusqueda'));
        $this->assertNotSame($primeraPagina, $segundaPagina);
    }
}