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
        // Acceder a la propiedad relacionada solo para esto
        $property = $this->property;
        $capital = $property->valor_requerido ?? 0;

        $tem = $this->tem;
        $temConIgv = $tem * 1.18;
        $temDecimal = $temConIgv / 100;

        $currencyMap = [
            1 => 'PEN',
            2 => 'USD',
        ];

        $monedaPropiedad = $currencyMap[$property->currency_id] ?? null;

        $investor = Auth::user();
        $mensaje = null;
        $mensajeConversion = null;
        $balanceDisponible = null;
        $cuotaMensual = null;
        $cuadroAmortizacion = $this->generateAmortizationTable($capital, $temDecimal);

        if ($investor) {
            $balances = $investor->balances;

            $balanceMonedaIgual = $balances->firstWhere('currency', $monedaPropiedad);
            $balanceOtraMoneda = $balances->firstWhere('currency', '!=', $monedaPropiedad);

            if ($balanceMonedaIgual) {
                $cuotaMensual = floatval(str_replace(',', '', $cuadroAmortizacion[0]['cuota_mensual'] ?? 0));
                $balanceDisponible = $balanceMonedaIgual->amount;

                $mensaje = 'Saldo disponible: ' . $balanceDisponible;
            } elseif ($balanceOtraMoneda) {
                $mensajeConversion = "La propiedad está en {$monedaPropiedad}. ¿Deseas convertir tus {$balanceOtraMoneda->currency} a {$monedaPropiedad}?";
            } else {
                $mensaje = 'No se encontraron balances disponibles.';
            }
        } else {
            $mensaje = 'No autenticado.';
        }

        return [
            'id' => $this->id,
            'property_id' => $property->id ?? null,
            'cuadro_amortizacion' => $cuadroAmortizacion,
            'mensaje_validacion_balance' => $mensaje,
            'mensaje_conversion_sugerida' => $mensajeConversion,
            'cuota_mensual_requerida' => $cuotaMensual,
            'moneda_propiedad' => $monedaPropiedad,
            'balance_disponible' => $balanceDisponible,
            'foto' => $this->getImagenes($property->id ?? null),
        ];
    }

    private function getImagenes($propertyId): array
    {
        $imagenes = [];

        if (!$propertyId) {
            return [asset('Propiedades/no-image.png')];
        }

        $rutaCarpeta = public_path("Propiedades/{$propertyId}");

        if (\File::exists($rutaCarpeta)) {
            foreach (\File::files($rutaCarpeta) as $archivo) {
                $imagenes[] = asset("Propiedades/{$propertyId}/" . $archivo->getFilename());
            }
        }

        return $imagenes ?: [asset('Propiedades/no-image.png')];
    }

    private function generateAmortizationTable($capital, $temDecimal): array
    {
        $plazos = Deadlines::select('id', 'duracion_meses')->get();
        $cuadroAmortizacion = [];

        foreach ($plazos as $plazo) {
            $n = $plazo->duracion_meses;

            if ($temDecimal == 0 || $n == 0) {
                continue;
            }

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
