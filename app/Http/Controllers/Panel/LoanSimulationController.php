<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\LoanSimulationService;
use Illuminate\Http\Request;

class LoanSimulationController extends Controller
{
    protected $simulationService;

    public function __construct(LoanSimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    /**
     * Realizar simulación de préstamo
     */
    public function simulate(Request $request)
    {
        $request->validate([
            'corporate_entity_id' => 'required|integer|exists:corporate_entities,id',
            'amount' => 'required|numeric|min:0.01',
            'days' => 'required|integer|min:1',
            'payment_frequency_id' => 'required|integer|exists:payment_frequencies,id'
        ]);

        // Validar parámetros específicos del negocio
        $validation_errors = $this->simulationService->validateSimulationParams(
            $request->corporate_entity_id,
            $request->amount,
            $request->days,
            $request->payment_frequency_id
        );

        if (!empty($validation_errors)) {
            return response()->json([
                'success' => false,
                'errors' => $validation_errors
            ], 400);
        }

        // Realizar simulación
        $result = $this->simulationService->simulate(
            $request->corporate_entity_id,
            $request->amount,
            $request->days,
            $request->payment_frequency_id
        );

        $status = $result['success'] ? 200 : 404;
        return response()->json($result, $status);
    }

    /**
     * Obtener opciones para simulación
     */
    public function getOptions()
    {
        $options = $this->simulationService->getSimulationOptions();
        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    /**
     * Obtener rangos de monto para una entidad
     */
    public function getAmountRanges($corporate_entity_id)
    {
        $ranges = $this->simulationService->getAmountRanges($corporate_entity_id);
        return response()->json([
            'success' => true,
            'data' => $ranges
        ]);
    }

    /**
     * Obtener planes de término
     */
    public function getTermPlans()
    {
        $plans = $this->simulationService->getTermPlans();
        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }
}