<?php

namespace Tests\Critical;

use App\Services\PaymentDistributionService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaymentDistributionServiceTest extends TestCase
{
    public function test_change_is_assigned_only_to_cash_and_net_total_matches_invoice(): void
    {
        $result = (new PaymentDistributionService())->calculate(100.00, [
            ['id' => 1, 'name' => 'Efectivo', 'amount' => 60.00],
            ['id' => 2, 'name' => 'Tarjeta', 'amount' => 50.00],
        ]);

        $this->assertSame(10.00, $result[0]['change']);
        $this->assertSame(0.00, $result[1]['change']);
        $this->assertSame(100.00, array_sum(array_column($result, 'net')));
    }

    public function test_underpayment_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PaymentDistributionService())->calculate(100.00, [
            ['id' => 2, 'name' => 'Tarjeta', 'amount' => 99.99],
        ]);
    }

    public function test_change_cannot_exceed_received_cash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PaymentDistributionService())->calculate(100.00, [
            ['id' => 1, 'name' => 'Efectivo', 'amount' => 5.00],
            ['id' => 2, 'name' => 'Tarjeta', 'amount' => 110.00],
        ]);
    }
}