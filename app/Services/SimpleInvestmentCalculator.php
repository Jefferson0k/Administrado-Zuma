<?php

namespace App\Services;

use App\Models\FixedTermRate;
use App\Models\PaymentFrequency;

class SimpleInvestmentCalculator
{
    public function calculate($corporateEntityId, $amount, $days, $paymentFrequencyId)
    {
        $paymentFreq = PaymentFrequency::findOrFail($paymentFrequencyId);

        $rate = FixedTermRate::where('corporate_entity_id', $corporateEntityId)
            ->where('estado', 'activo')
            ->whereHas('amountRange', function ($query) use ($amount) {
                $query->where('desde', '<=', $amount)
                      ->where(function ($q) use ($amount) {
                          $q->whereNull('hasta')
                            ->orWhere('hasta', '>=', $amount);
                      });
            })
            ->whereHas('termPlan', function ($query) use ($days) {
                $query->where('dias_minimos', '<=', $days)
                      ->where('dias_maximos', '>=', $days);
            })
            ->first();

        if (!$rate) {
            throw new \Exception('No se encontró tasa aplicable');
        }

        $tea = $rate->valor / 100;
        $totalInterest = $amount * $tea;
        $payments = $this->calculatePayments($totalInterest, $days, $paymentFreq);

        return [
            'monto_invertir' => $amount,
            'tea' => $rate->valor . '%',
            'dias' => $days,
            'tasa_retornar' => round($totalInterest, 2),
            'monto_retornar' => round($amount + $totalInterest, 2),
            'pagos' => $payments
        ];
    }

    private function calculatePayments($totalInterest, $days, $paymentFreq)
    {
        $periodDays = $paymentFreq->dias;
        $periodCount = ceil($days / $periodDays);

        $paymentAmount = round($totalInterest / $periodCount, 2);
        $payments = [];
        $pagadoAcumulado = 0;

        for ($i = 1; $i <= $periodCount; $i++) {
            if ($i === $periodCount) {
                $montoPago = round($totalInterest - $pagadoAcumulado, 2);
            } else {
                $montoPago = $paymentAmount;
                $pagadoAcumulado += $montoPago;
            }

            $payments[] = [
                'pago' => $montoPago,
                'periodo' => "Periodo $i ({$paymentFreq->nombre})"
            ];
        }

        return $payments;
    }
}
