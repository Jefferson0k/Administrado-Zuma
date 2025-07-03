<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Deadlines;
use Illuminate\Support\Facades\Auth;

class PropertyShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $capital = $this->valor_estimado;
        $tem = $this->tem;
        $temConIgv = $tem * 1.18;
        $temDecimal = $temConIgv / 100;

        $currencyMap = [
            1 => 'PEN',
            2 => 'USD',
        ];

        $monedaPropiedad = $currencyMap[$this->currency_id] ?? null;

        $investor = Auth::user();
        $puedePagar = false;
        $mensaje = null;
        $mensajeConversion = null;
        $balanceDisponible = null;
        $cuotaMensual = null;
        $cuadroAmortizacion = null;

        if ($investor) {
            $balances = $investor->balances;

            // Buscar balance con la misma moneda
            $balanceMonedaIgual = $balances->firstWhere('currency', $monedaPropiedad);

            // Buscar balance con moneda distinta (para sugerencia)
            $balanceOtraMoneda = $balances->firstWhere('currency', '!=', $monedaPropiedad);

            if ($balanceMonedaIgual) {
                $cuadroAmortizacion = $this->generateAmortizationTable($capital, $temDecimal);
                $cuotaMensual = floatval(str_replace(',', '', $cuadroAmortizacion[0]['cuota_mensual'] ?? 0));
                $balanceDisponible = $balanceMonedaIgual->amount;

                if ($balanceDisponible >= $cuotaMensual) {
                    $puedePagar = true;
                    $mensaje = 'El inversor puede cubrir la primera cuota.';
                } else {
                    $mensaje = 'Saldo insuficiente para cubrir la primera cuota.';
                    $cuadroAmortizacion = null;
                }
            } elseif ($balanceOtraMoneda) {
                $mensajeConversion = "La propiedad está en {$monedaPropiedad}. ¿Deseas convertir tus {$balanceOtraMoneda->currency} a {$monedaPropiedad} para cubrir la primera cuota?";
            } else {
                $mensaje = 'No se encontraron balances disponibles.';
            }
        } else {
            $mensaje = 'No autenticado.';
        }

        return $puedePagar
            ? [
                'id' => $this->id,
                'cuadro_amortizacion' => $cuadroAmortizacion,
                'mensaje_validacion_balance' => $mensaje,
                'cuota_mensual_requerida' => $cuotaMensual,
                'moneda_propiedad' => $monedaPropiedad,
                'balance_disponible' => $balanceDisponible,
            ]
            : [
                'id' => $this->id,
                'mensaje_validacion_balance' => $mensaje,
                'mensaje_conversion_sugerida' => $mensajeConversion,
            ];
    }

    private function generateAmortizationTable($capital, $temDecimal)
    {
        $plazos = Deadlines::select('id', 'duracion_meses')->get();
        $cuadroAmortizacion = [];

        foreach ($plazos as $plazo) {
            $n = $plazo->duracion_meses;

            $cuota = $capital * ($temDecimal * pow(1 + $temDecimal, $n)) /
                     (pow(1 + $temDecimal, $n) - 1);
            $total = $cuota * $n;

            $cuadroAmortizacion[] = [
                'deadline_id' => $plazo->id,
                'plazo_meses' => $n,
                'cuota_mensual' => number_format($cuota, 2, '.', ','),
                'total_pagado' => number_format($total, 2, '.', ',')
            ];
        }

        return $cuadroAmortizacion;
    }
}
