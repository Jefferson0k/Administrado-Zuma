<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleGeneratorService{
    public function generate(array $data): Investment{
        return DB::transaction(function () use ($data) {
            $investment = Investment::create([
                'customer_id'     => $data['customer_id'],
                'property_id'     => $data['property_id'],
                'term_id'         => $data['term_id'],
                'monto_invertido' => $data['monto'],
                'fecha_inversion' => $data['fecha_desembolso'],
                'estado'          => 'activa',
            ]);
            $capital = $data['monto'];
            $plazo = $data['plazo'];
            $tea = $data['tea'];
            $tem = $data['tem'];
            $fechaInicio = Carbon::parse($data['fecha_inicio']);
            $igvRate = 0.123;

            $temDecimal = $tem / 100;
            $cuotaNeta = $capital * ($temDecimal * pow(1 + $temDecimal, $plazo)) / (pow(1 + $temDecimal, $plazo) - 1);
            $cuotaNeta = round($cuotaNeta, 2);

            $saldoInicial = $capital;

            for ($i = 1; $i <= $plazo; $i++) {
                $interes = round($saldoInicial * $temDecimal, 2);
                $capitalPagado = round($cuotaNeta - $interes, 2);
                $saldoFinal = round($saldoInicial - $capitalPagado, 2);
                $igv = round($interes * $igvRate, 2);
                $cuotaTotal = round($cuotaNeta + $igv, 2);
                $fechaVencimiento = $fechaInicio->copy()->addMonths($i - 1);

                PaymentSchedule::create([
                    'investment_id'     => $investment->id,
                    'nro_cuota'         => $i,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'saldo_inicial'     => $saldoInicial,
                    'capital'           => $capitalPagado,
                    'interes'           => $interes,
                    'cuota_neta'        => $cuotaNeta,
                    'igv'               => $igv,
                    'cuota_total'       => $cuotaTotal,
                    'saldo_final'       => $saldoFinal,
                ]);
                $saldoInicial = $saldoFinal;
            }
            return $investment;
        });
    }
}
