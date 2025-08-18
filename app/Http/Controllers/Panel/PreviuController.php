<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\CreditSimulationFrancesPreviewService;
use App\Services\CreditSimulationPreviewService;
use Illuminate\Http\Request;

class PreviuController extends Controller{
    public function previewFrances(Request $request){
        $simulador = new CreditSimulationFrancesPreviewService();
        $cronograma = $simulador->generate(
    floatval($request->input('valor_requerido')),
            floatval($request->input('tem')),
            floatval($request->input('tea')),
            intval($request->input('plazo')),
            intval($request->input('moneda_id', 1))
        );
        return response()->json($cronograma);
    }
    public function previewManual(Request $request){
        $simulador = new CreditSimulationPreviewService();
        $cronograma = $simulador->generate(
    floatval($request->input('valor_requerido')),
    floatval($request->input('tem')),
    floatval($request->input('tea')),
    intval($request->input('plazo')),
    intval($request->input('moneda_id', 1))
        );
        return response()->json($cronograma);
    }
}
