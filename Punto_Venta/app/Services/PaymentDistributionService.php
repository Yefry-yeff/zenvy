<?php

namespace App\Services;

use InvalidArgumentException;

class PaymentDistributionService
{
    public function calculate(float $invoiceTotal, array $payments): array
    {
        $payments = array_values(array_filter(
            $payments,
            fn (array $payment): bool => (float) $payment['amount'] > 0
        ));

        $received = round(array_sum(array_column($payments, 'amount')), 2);
        $invoiceTotal = round($invoiceTotal, 2);

        if ($received + 0.009 < $invoiceTotal) {
            throw new InvalidArgumentException('La distribución de pagos es menor que el total de la factura.');
        }

        $change = round($received - $invoiceTotal, 2);
        $cashIndex = null;

        foreach ($payments as $index => $payment) {
            if (str_contains(mb_strtolower($payment['name']), 'efectivo')) {
                $cashIndex = $index;
                break;
            }
        }

        if ($change > 0 && $cashIndex === null) {
            throw new InvalidArgumentException('El excedente solo puede devolverse como cambio en efectivo.');
        }

        if ($change > 0 && $change > (float) $payments[$cashIndex]['amount']) {
            throw new InvalidArgumentException('El cambio no puede ser mayor que el efectivo recibido.');
        }

        return array_map(function (array $payment, int $index) use ($cashIndex, $change): array {
            $amount = round((float) $payment['amount'], 2);
            $paymentChange = $index === $cashIndex ? $change : 0.0;

            return [
                'payment_type_id' => (int) $payment['id'],
                'name' => $payment['name'],
                'amount' => $amount,
                'change' => $paymentChange,
                'net' => round($amount - $paymentChange, 2),
            ];
        }, $payments, array_keys($payments));
    }
}