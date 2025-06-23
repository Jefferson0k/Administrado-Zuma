<?php

namespace App\Services;

use App\Models\FixedTermRate;

class InvestmentSimulatorService
{
    public function simulateByAmount(float $amount): array
    {
        $rates = FixedTermRate::with(['amountRange', 'termPlan', 'corporateEntity'])
            ->where('estado', 'activo')
            ->whereHas('amountRange', function ($query) use ($amount) {
                $query->where('desde', '<=', $amount)
                    ->where(function ($q) use ($amount) {
                        $q->whereNull('hasta')->orWhere('hasta', '>=', $amount);
                    });
            })
            ->get();

        if ($rates->isEmpty()) {
            throw new \Exception('No se encontraron tasas para este monto');
        }

        $agrupado = $rates->groupBy('corporate_entity_id');
        $resultado = [];

        foreach ($agrupado as $ratesPorCoop) {
            $cooperativa = $ratesPorCoop->first()->corporateEntity;

            $tasas = $ratesPorCoop->map(function ($rate) use ($amount) {
                $tasaRow = [
                    'plazo_dias' => $rate->termPlan->dias_maximos
                ];

                if ($rate->valor_trea !== null && $rate->valor_trem !== null) {
                    $tasaRow['TREA'] = number_format($rate->valor_trea, 2) . '%';
                    $tasaRow['TREM'] = number_format($rate->valor_trem, 2) . '%';

                    $interesTREA = round($amount * ($rate->valor_trea / 100), 2);
                    $interesTREM = round($amount * ($rate->valor_trem / 100), 2);

                    $tasaRow['retorno_trea'] = 'S/ ' . number_format($interesTREA, 2, ',', '.');
                    $tasaRow['retorno_trem'] = 'S/ ' . number_format($interesTREM, 2, ',', '.');

                    // Valor mixto para orden
                    $tasaRow['orden_tasa'] = ($rate->valor_trea + $rate->valor_trem) / 2;

                } elseif ($rate->valor !== null) {
                    $tasaRow['TEA'] = number_format($rate->valor, 2) . '%';
                    $interes = round($amount * ($rate->valor / 100), 2);
                    $tasaRow['retorno'] = 'S/ ' . number_format($interes, 2, ',', '.');
                    $tasaRow['orden_tasa'] = $rate->valor;

                } elseif ($rate->valor_tem !== null) {
                    $tasaRow['TEM'] = number_format($rate->valor_tem, 2) . '%';
                    $interes = round($amount * ($rate->valor_tem / 100), 2);
                    $tasaRow['retorno'] = 'S/ ' . number_format($interes, 2, ',', '.');
                    $tasaRow['orden_tasa'] = $rate->valor_tem;
                }

                return $tasaRow;
            })->sortByDesc(function ($tasa) {
                return $tasa['orden_tasa'] ?? 0;
            })->values();

            $resultado[] = [
                'cooperativa' => $cooperativa->nombre,
                'tipo_columnas' => $this->detectarColumnas($tasas->first()),
                'tasas' => $tasas->map(fn($tasa) => collect($tasa)->except('orden_tasa')),
                'mejor_tasa' => $tasas->first()['orden_tasa'] ?? 0
            ];
        }

        usort($resultado, fn($a, $b) => $b['mejor_tasa'] <=> $a['mejor_tasa']);

        $ordenes = ['Primera', 'Segunda', 'Tercera', 'Cuarta', 'Quinta'];
        foreach ($resultado as $index => &$coop) {
            $coop['orden'] = $ordenes[$index] ?? ($index + 1) . '°';
            unset($coop['mejor_tasa']);
        }

        return $resultado;
    }

    private function detectarColumnas($tasa)
    {
        if (isset($tasa['TREA']) && isset($tasa['TREM'])) {
            return ['TREA', 'TREM', 'plazo_dias', 'retorno_trea', 'retorno_trem'];
        }

        if (isset($tasa['TEA'])) {
            return ['TEA', 'plazo_dias', 'retorno'];
        }

        if (isset($tasa['TEM'])) {
            return ['TEM', 'plazo_dias', 'retorno'];
        }

        return ['plazo_dias'];
    }
}
