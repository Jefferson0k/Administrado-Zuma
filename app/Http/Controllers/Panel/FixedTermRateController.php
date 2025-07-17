<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tasas\FixedTermInvestment\FixedTermRateResource;
use App\Models\AmountRange;
use App\Models\FixedTermRate;
use App\Models\TermPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FixedTermRateController extends Controller{
    public function matrix($empresaId){
        $tasas = FixedTermRate::with(['amountRange', 'termPlan'])
            ->where('corporate_entity_id', $empresaId)
            ->get();

        $plazosConTasas = $tasas->pluck('term_plan_id')->unique();

        $plazos = TermPlan::whereIn('id', $plazosConTasas)
            ->orderBy('dias_minimos')
            ->get(['id', 'nombre']);
        $plazoIds = $plazos->pluck('id');
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
                foreach ($plazoIds as $plazoId) {
                    $matriz[$moneda][$rangoId]['tasas'][$plazoId] = null;
                }
            }
            $matriz[$moneda][$rangoId]['tasas'][$tasa->term_plan_id] = [
                'id' => $tasa->id,
                'valor' => $tasa->valor
            ];
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
    public function store(Request $request){
        $validated = $request->validate([
            'corporate_entity_id' => 'required|exists:corporate_entities,id',
            'amount_range_id' => 'required|exists:amount_ranges,id',
            'term_plan_id' => 'required|array|min:1',
            'term_plan_id.*' => 'required|exists:term_plans,id',
            'rate_type_id' => 'required|exists:rate_types,id'
        ]);
        $registrosCreados = [];
        try {
            DB::beginTransaction();
            foreach ($validated['term_plan_id'] as $termPlanId) {
                $fixedTermRate = FixedTermRate::create([
                    'corporate_entity_id' => $validated['corporate_entity_id'],
                    'amount_range_id' => $validated['amount_range_id'],
                    'term_plan_id' => $termPlanId,
                    'rate_type_id' => $validated['rate_type_id'],
                ]);
                
                $registrosCreados[] = $fixedTermRate;
            }
            AmountRange::where('id', $validated['amount_range_id'])->update([
                'estado' => 'activo'
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Registros creados exitosamente',
                'data' => $registrosCreados,
                'total_creados' => count($registrosCreados)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear los registros',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id){
        $request->validate([
            'valor' => 'required|numeric',
        ]);
        $fixedTermRate = FixedTermRate::find($id);
        if (!$fixedTermRate) {
            return response()->json(['message' => 'Tasa no encontrada.'], 404);
        }
        $fixedTermRate->valor = $request->input('valor');
        $fixedTermRate->save();
        return response()->json([
            'message' => 'Tasa actualizada correctamente.',
            'data' => $fixedTermRate,
        ], 200);
    }
}
