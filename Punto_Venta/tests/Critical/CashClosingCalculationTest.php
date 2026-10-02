<?php

namespace Tests\Critical;

use App\Livewire\Caja\CierreDeCaja;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CashClosingCalculationTest extends TestCase
{
    public function test_denominations_and_payment_differences_are_calculated_consistently(): void
    {
        $closing = new CierreDeCaja();
        $closing->billetes_500 = 2;
        $closing->billetes_100 = 1;
        $closing->monedas_0_50 = 3;
        $closing->resumenTransacciones = new Collection([
            (object) ['forma_pago' => 'Tarjeta (POS)', 'total' => 200.00],
            (object) ['forma_pago' => 'Transferencia', 'total' => 150.00],
            (object) ['forma_pago' => 'Cheque', 'total' => 50.00],
        ]);
        $closing->facturasAnuladasTarjeta = 20.00;
        $closing->facturasAnuladasTransferencia = 0.00;
        $closing->facturasAnuladasCheque = 10.00;
        $closing->totalTarjetaContado = 185.00;
        $closing->totalTransferenciaContado = 145.00;
        $closing->totalChequeContado = 45.00;

        $closing->calcularTotalContado();
        $closing->calcularDiferencias();

        $this->assertSame(1101.50, $closing->totalContado);
        $this->assertSame(5.00, $closing->diferenciaTarjeta);
        $this->assertSame(-5.00, $closing->diferenciaTransferencia);
        $this->assertSame(5.00, $closing->diferenciaCheque);
    }
}