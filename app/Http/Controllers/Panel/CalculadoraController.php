<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CalculadoraController extends Controller{
    public function calcular(Request $request): JsonResponse{
        $tipoCambio = 3.75;
        $porcentaje = 40; 

        $validated = $request->validate([
            'ingresos' => 'required|numeric|min:0',
            'egresos' => 'required|numeric|min:0',
        ]);
        
        $ingresosSoles = $validated['ingresos'];
        $egresosSoles = $validated['egresos'];

        $ingresosDolares = $ingresosSoles / $tipoCambio;
        $egresosDolares = $egresosSoles / $tipoCambio;

        $disponibleSoles = $ingresosSoles - $egresosSoles;
        $cuotaIdealSoles = $disponibleSoles * ($porcentaje / 100);
        $cuotaIdealDolares = $cuotaIdealSoles / $tipoCambio;

        return response()->json([
            'tipo_cambio' => $tipoCambio,
            'porcentaje' => $porcentaje,
            'ingresos_dolares' => round($ingresosDolares, 8),
            'egresos_dolares' => round($egresosDolares, 8),
            'cuota_ideal_dolares' => round($cuotaIdealDolares, 8),
            'cuota_ideal_soles' => round($cuotaIdealSoles, 8),
        ]);
    }
}
