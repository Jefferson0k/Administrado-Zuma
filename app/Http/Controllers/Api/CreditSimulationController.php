<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simulador\CronogramaSimulationRequest;
use App\Models\Property;
use App\Models\Deadlines;
use App\Services\CreditSimulationService;
use Illuminate\Http\JsonResponse;

class CreditSimulationController extends Controller{
    protected CreditSimulationService $service;
    public function __construct(CreditSimulationService $service){
        $this->service = $service;
    }

    public function generateSimulation(CronogramaSimulationRequest $request): JsonResponse{
        try {
            $property = Property::findOrFail($request->property_id);
            $deadline = Deadlines::findOrFail($request->deadline_id);

            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            $cronograma = $this->service->generate($property, $deadline, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $cronograma
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

}
