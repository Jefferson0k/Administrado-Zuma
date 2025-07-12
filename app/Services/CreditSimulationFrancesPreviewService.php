<?php

namespace App\Services;

use Carbon\Carbon;

class CreditSimulationFrancesPreviewService
{
    public function generate(
        float $valor_requerido,
        float $tem,
        float $tea,
        int $plazoMeses,
        int $currencyId = 1
    ): array {
        $capital = $valor_requerido;
        $tem_sin_igv = $tem / 100;
        $moneda = $currencyId === 1 ? 'Soles' : 'Dólares';
        $simbolo = $currencyId === 1 ? 'PEN' : 'USD';
        $fechaDesembolso = Carbon::now()->format('d/m/Y');
        $fechaInicio = Carbon::now()->addMonth()->day(15);
        $saldoInicial = $capital;
        $pagos = [];
        for ($cuota = 1; $cuota <= $plazoMeses; $cuota++) {
            $interesSinIGV = $saldoInicial * $tem_sin_igv;
            $capitalPago = $this->calcularCapitalPago($capital, $tem_sin_igv, $plazoMeses, $cuota);
            $cuotaNeta = $capitalPago + $interesSinIGV;
            $cuotaTotal = $cuotaNeta;
            $saldoFinal = $saldoInicial - $capitalPago;
            if ($cuota === $plazoMeses && abs($saldoFinal) < 0.01) {
                $capitalPago = $saldoInicial;
                $interesSinIGV = $capitalPago * $tem_sin_igv;
                $cuotaNeta = $capitalPago + $interesSinIGV;
                $cuotaTotal = $cuotaNeta;
                $saldoFinal = 0.00;
            }
            $fechaVcmto = $fechaInicio->copy()->addMonths($cuota - 1)->format('d/m/Y');
            $pagos[] = [
                'cuota' => $cuota,
                'vcmto' => $fechaVcmto,
                'saldo_inicial' => number_format($saldoInicial, 2, '.', ''),
                'capital' => number_format($capitalPago, 2, '.', ''),
                'interes' => number_format($interesSinIGV, 2, '.', ''),
                'cuota_neta' => number_format($cuotaNeta, 2, '.', ''),
                'igv' => number_format(0, 2, '.', ''),
                'total_cuota' => number_format($cuotaTotal, 2, '.', ''),
                'saldo_final' => number_format($saldoFinal, 2, '.', ''),
            ];
            $saldoInicial = $saldoFinal;
        }
        return [
            'cliente' => 'SIMULACIÓN SISTEMA FRANCÉS',
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
    private function calcularCapitalPago(float $capital, float $tem, int $plazo, int $periodo): float
    {
        $factor = pow(1 + $tem, $plazo);
        $cuota = $capital * ($tem * $factor) / ($factor - 1);
        $saldo = $capital;
        for ($i = 1; $i < $periodo; $i++) {
            $interes = $saldo * $tem;
            $capitalPago = $cuota - $interes;
            $saldo -= $capitalPago;
        }
        $interes = $saldo * $tem;
        return $cuota - $interes;
    }
}
