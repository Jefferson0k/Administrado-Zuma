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
            valorEstimado: floatval($request->input('valor_estimado')),
            tem: floatval($request->input('tem')),
            tea: floatval($request->input('tea')),
            plazoMeses: intval($request->input('plazo')),
            currencyId: intval($request->input('moneda_id', 1))
        );

        return response()->json($cronograma);
    }
    public function previewManual(Request $request){
        $simulador = new CreditSimulationPreviewService();
        $cronograma = $simulador->generate(
            valorEstimado: floatval($request->input('valor_estimado')),
            tem: floatval($request->input('tem')),
            tea: floatval($request->input('tea')),
            plazoMeses: intval($request->input('plazo')),
            currencyId: intval($request->input('moneda_id', 1)) // opcional
        );
        return response()->json($cronograma);
    }
}
