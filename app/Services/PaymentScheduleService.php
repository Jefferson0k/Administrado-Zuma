<?php

namespace App\Services;

use Carbon\Carbon;

class PaymentScheduleService{
    public function generarCronograma(array $datos): array{
        $monto = $datos['monto'];
        $tasa = $datos['tasa'];
        $tipo_tasa = strtoupper($datos['tipo_tasa']);
        $plazo_dias = $datos['plazo'];
        $frecuencia_dias = $datos['frecuencia'];
        $capital_al_final = $datos['capital_al_final'] ?? true;
        $impuesto_pct = $datos['impuesto'] ?? 5;

        $num_cuotas = intval($plazo_dias / $frecuencia_dias);
        $fecha_inicio = Carbon::now();
        $capital = $monto;

        $cronograma = [];

        for ($i = 1; $i <= $num_cuotas; $i++) {
            $fecha_pago = $fecha_inicio->copy()->addDays($frecuencia_dias * $i);

            if ($tipo_tasa === 'TEA') {
                $tem = pow(1 + ($tasa / 100), $frecuencia_dias / 360) - 1;
            } elseif ($tipo_tasa === 'TEM') {
                $tem = $tasa / 100;
            } else {
                throw new \Exception("Tipo de tasa no soportado: $tipo_tasa");
            }

            $interes = round($capital * $tem, 2);
            $impuesto = round($interes * $impuesto_pct / 100, 2);
            $interes_neto = round($interes - $impuesto, 2);

            $devolucion_capital = ($capital_al_final && $i == $num_cuotas) ? $capital : 0;
            $total = $interes_neto + $devolucion_capital;

            $cronograma[] = [
                'mes' => $i,
                'fecha_pago' => $fecha_pago->format('Y-m-d'),
                'dias' => $frecuencia_dias,
                'monto_base' => $capital,
                'interes' => $interes,
                'impuesto' => $impuesto,
                'interes_neto' => $interes_neto,
                'devolucion_capital' => $devolucion_capital,
                'saldo_capital' => $capital,
                'total_depositar' => $total
            ];
        }

        return $cronograma;
    }
}
