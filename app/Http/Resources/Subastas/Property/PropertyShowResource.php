<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Deadlines;

class PropertyShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $capital = $this->valor_estimado;
        $tem = $this->tem;
        $temConIgv = $tem * 1.18;
        $temDecimal = $temConIgv / 100;

        $cuadroAmortizacion = $this->generateAmortizationTable($capital, $temDecimal);

        return [
            'id' => $this->id,
            'cuadro_amortizacion' => $cuadroAmortizacion
        ];
    }

    private function generateAmortizationTable($capital, $temDecimal){
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
