<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\FixedTermRate;
use App\Models\TermPlan;

class FixedTermRateController extends Controller{
    public function matrix($empresaId){
        $tasas = FixedTermRate::with(['amountRange', 'termPlan'])
            ->where('corporate_entity_id', $empresaId)
            ->get();

        $plazosConTasas = $tasas->pluck('term_plan_id')->unique();
        
        $plazos = TermPlan::whereIn('id', $plazosConTasas)
            ->orderBy('dias_minimos')
            ->get(['id', 'nombre']);

        $matriz = [];
        
        foreach ($tasas as $tasa) {
            $moneda = $tasa->amountRange->moneda;
            $rangoId = $tasa->amountRange->id;
            $rangoLabel = 'S/ ' . number_format($tasa->amountRange->desde, 2) . ' - ' .
                ($tasa->amountRange->hasta ? number_format($tasa->amountRange->hasta, 2) : 'En adelante');

            if (!isset($matriz[$moneda][$rangoId])) {
                $matriz[$moneda][$rangoId] = [
                    'rangoId' => $rangoId,
                    'rango' => $rangoLabel,
                    'tasas' => []
                ];
            }

            $matriz[$moneda][$rangoId]['tasas'][$tasa->term_plan_id] = $tasa->valor;
        }

        $resultado = [];
        foreach ($matriz as $moneda => $rangos) {
            $resultado[$moneda] = array_values($rangos);
        }

        return response()->json([
            'matriz' => $resultado,
            'plazos' => $plazos
        ]);
    }
}
