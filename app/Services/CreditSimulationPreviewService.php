<?php

namespace App\Services;

use Carbon\Carbon;

class CreditSimulationPreviewService
{
    public function generate(
        float $valorEstimado,   // Capital solicitado
        float $tem,             // Tasa efectiva mensual (%)
        float $tea,             // Tasa efectiva anual (%)
        int $plazoMeses,        // Plazo en meses
        int $currencyId = 1     // 1 = Soles, otro = Dólares
    ): array {
        $capital = $valorEstimado;
        $tem_sin_igv = $tem / 100;

        $moneda = $currencyId === 1 ? 'Soles' : 'Dólares';
        $simbolo = $currencyId === 1 ? 'PEN' : 'USD';

        $fechaDesembolso = Carbon::now()->format('d/m/Y');
        $fechaInicio = Carbon::now()->addMonth()->day(15);
        $pagos = [];

        for ($cuota = 1; $cuota <= $plazoMeses; $cuota++) {
            $interesSinIGV = $capital * $tem_sin_igv;
            $igv = 0.00;
            $capitalPago = ($cuota === $plazoMeses) ? $capital : 0.00;
            $cuotaNeta = $interesSinIGV + $capitalPago;
            $cuotaTotal = $cuotaNeta;
            $saldoInicial = $capital;
            $saldoFinal = ($cuota === $plazoMeses) ? 0.00 : $capital;
            $fechaVcmto = $fechaInicio->copy()->addMonths($cuota - 1)->format('d/m/Y');

            $pagos[] = [
                'cuota' => $cuota,
                'vcmto' => $fechaVcmto,
                'saldo_inicial' => number_format($saldoInicial, 2, '.', ''),
                'capital' => number_format($capitalPago, 2, '.', ''),
                'interes' => number_format($interesSinIGV, 2, '.', ''),
                'cuota_neta' => number_format($cuotaNeta, 2, '.', ''),
                'igv' => number_format($igv, 2, '.', ''),
                'total_cuota' => number_format($cuotaTotal, 2, '.', ''),
                'saldo_final' => number_format($saldoFinal, 2, '.', ''),
            ];
        }

        return [
            'cliente' => 'SIMULACIÓN PREVIA',
            'monto_solicitado' => number_format($capital, 2, '.', ''),
            'plazo_credito' => $plazoMeses,
            'tasa_efectiva_mensual' => number_format($tem, 4, '.', '') . '%',
            'tasa_efectiva_anual' => number_format($tea, 4, '.', '') . '%',
            'fecha_desembolso' => $fechaDesembolso . ' referencial',
            'cronograma_final' => [
                'plazo_total' => $plazoMeses,
                'moneda' => $moneda,
                'capital_otorgado' => $simbolo . ' ' . number_format($capital, 2, '.', ''),
                'tea_compensatoria' => number_format($tea, 2, '.', '') . ' %',
                'tem_compensatoria' => number_format($tem, 2, '.', '') . ' %',
                'pagos' => $pagos,
                'pagination' => [
                    'total' => count($pagos),
                    'per_page' => count($pagos),
                    'current_page' => 1,
                    'last_page' => 1
                ]
            ]
        ];
    }
}
