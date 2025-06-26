<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Deadlines;
use Carbon\Carbon;

class CreditSimulationService
{
    public function generate(Property $property, Deadlines $deadline, int $page = 1, int $perPage = 10): array
    {
        $capital = floatval($property->valor_estimado);
        $plazoMeses = $deadline->duracion_meses;
        $moneda = $property->currency_id == 1 ? 'Soles' : 'Dólares';
        $simbolo = $property->currency_id == 1 ? 'PEN' : 'USD';

        // Tasas
        $tem_sin_igv = floatval($property->tem) / 100;       // 1.8088%
        $tem_con_igv = $tem_sin_igv * 1.18;                  // 2.1343%
        $fechaDesembolso = Carbon::now()->format('d/m/Y');
        $fechaInicio = Carbon::now()->addMonth()->day(15);
        $saldoInicial = $capital;
        $pagos = [];

        for ($cuota = 1; $cuota <= $plazoMeses; $cuota++) {
            // Interés con IGV
            $interesConIGV = $saldoInicial * $tem_con_igv;

            // Interés sin IGV
            $interesSinIGV = $interesConIGV / 1.18;

            // IGV del interés
            $igv = $interesConIGV - $interesSinIGV;

            // Capital pagado
            $cuotaNeta = $interesSinIGV + $this->calcularCapitalPago($capital, $tem_con_igv, $plazoMeses, $cuota); // variable
            $capitalPago = $cuotaNeta - $interesSinIGV;

            // Total cuota
            $cuotaTotal = $capitalPago + $interesConIGV;

            // Saldo final
            $saldoFinal = $saldoInicial - $capitalPago;

            // Corrección redondeo en última cuota
            if ($cuota === $plazoMeses && abs($saldoFinal) < 0.01) {
                $capitalPago = $saldoInicial;
                $interesConIGV = $capitalPago * $tem_con_igv;
                $interesSinIGV = $interesConIGV / 1.18;
                $igv = $interesConIGV - $interesSinIGV;
                $cuotaTotal = $capitalPago + $interesConIGV;
                $cuotaNeta = $capitalPago + $interesSinIGV;
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
                'igv' => number_format($igv, 2, '.', ''),
                'total_cuota' => number_format($cuotaTotal, 2, '.', ''),
                'saldo_final' => number_format($saldoFinal, 2, '.', ''),
            ];

            $saldoInicial = $saldoFinal;
        }

        // Paginación
        $total = count($pagos);
        $offset = ($page - 1) * $perPage;
        $paginatedPagos = array_slice($pagos, $offset, $perPage);

        return [
            'cliente' => 'CLIENTE SIMULACION',
            'monto_solicitado' => number_format($capital, 2, '.', ''),
            'plazo_credito' => $plazoMeses,
            'tasa_efectiva_mensual' => number_format($property->tem, 4, '.', '') . '%',
            'tasa_efectiva_anual' => number_format($property->tea, 4, '.', '') . '%',
            'fecha_desembolso' => $fechaDesembolso . ' referencial',
            'cronograma_final' => [
                'plazo_total' => $plazoMeses,
                'moneda' => $moneda,
                'capital_otorgado' => $simbolo . ' ' . number_format($capital, 2, '.', ''),
                'tea_compensatoria' => number_format($property->tea, 2, '.', '') . ' %',
                'tem_compensatoria' => number_format($property->tem, 2, '.', '') . ' %',
                'pagos' => $paginatedPagos,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ]
        ];
    }

    private function calcularCapitalPago(float $capital, float $temConIGV, int $plazo, int $periodo): float
    {
        // Cálculo de cuota total (con IGV embebido en TEM)
        $factor = pow(1 + $temConIGV, $plazo);
        $cuota = $capital * ($temConIGV * $factor) / ($factor - 1);

        // Para el periodo específico se recalcula el saldo y capital amortizado
        $saldo = $capital;
        for ($i = 1; $i < $periodo; $i++) {
            $interes = $saldo * $temConIGV;
            $capitalPago = $cuota - $interes;
            $saldo -= $capitalPago;
        }

        $interes = $saldo * $temConIGV;
        return $cuota - $interes;
    }
}
