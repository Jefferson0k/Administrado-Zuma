<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InvestmentSimulatorService;
use Illuminate\Http\JsonResponse;

class InvestmentController extends Controller{
    protected $simulatorService;
    public function __construct(InvestmentSimulatorService $simulatorService){
        $this->simulatorService = $simulatorService;
    }
    public function simulateByAmount(Request $request): JsonResponse{
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        try {
            $simulations = $this->simulatorService->simulateByAmount($request->amount);
            
            return response()->json([
                'success' => true,
                'data' => $simulations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function generateSchedule(Request $request): JsonResponse {
        $request->validate([
            'rate_id' => 'required|integer|exists:fixed_term_rates,id',
            'amount' => 'required|numeric|min:1',
            'payment_frequency_id' => 'required|integer|exists:payment_frequencies,id',
            'generation_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'tax_rate' => 'nullable|numeric|min:0|max:1'
        ]);

        try {
            $schedule = $this->simulatorService->generatePaymentSchedule(
                $request->rate_id,
                $request->amount,
                $request->payment_frequency_id,
                $request->generation_date,
                $request->start_date,
                $request->tax_rate ?? 0.05
            );
                        
            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function getPaymentFrequencies(): JsonResponse{
        try {
            $frequencies = $this->simulatorService->getPaymentFrequencies();
            
            return response()->json([
                'success' => true,
                'data' => $frequencies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function completeSimulation(Request $request): JsonResponse{
        $request->validate([
            'rate_id' => 'required|integer|exists:fixed_term_rates,id',
            'amount' => 'required|numeric|min:1'
        ]);

        try {
            $simulations = $this->simulatorService->completeSimulation(
                $request->rate_id,
                $request->amount
            );
            
            return response()->json([
                'success' => true,
                'data' => $simulations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function compareRates(Request $request): JsonResponse{
        $request->validate([
            'rate_ids' => 'required|array|min:2|max:5',
            'rate_ids.*' => 'integer|exists:fixed_term_rates,id',
            'amount' => 'required|numeric|min:1',
            'payment_frequency_id' => 'required|integer|exists:payment_frequencies,id'
        ]);

        try {
            $comparisons = [];
            
            foreach ($request->rate_ids as $rateId) {
                $schedule = $this->simulatorService->generatePaymentSchedule(
                    $rateId,
                    $request->amount,
                    $request->payment_frequency_id
                );
                
                $comparisons[] = [
                    'rate_id' => $rateId,
                    'cooperativa' => $schedule['cooperativa'],
                    'tea' => $schedule['tea_aplicada'],
                    'rentabilidad_neta' => $schedule['resumen']['rentabilidad_neta'],
                    'total_interes_neto' => $schedule['resumen']['total_interes_neto'],
                    'total_a_recibir' => $schedule['resumen']['total_a_recibir'],
                    'schedule_summary' => $schedule['resumen'],
                    'full_schedule' => $schedule['cronograma']
                ];
            }
            
            usort($comparisons, fn($a, $b) => $b['rentabilidad_neta'] <=> $a['rentabilidad_neta']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'comparisons' => $comparisons,
                    'best_option' => $comparisons[0] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function exportSchedule(Request $request): JsonResponse{
        $request->validate([
            'rate_id' => 'required|integer|exists:fixed_term_rates,id',
            'amount' => 'required|numeric|min:1',
            'payment_frequency_id' => 'required|integer|exists:payment_frequencies,id',
            'format' => 'required|in:excel,pdf'
        ]);

        try {
            $schedule = $this->simulatorService->generatePaymentSchedule(
                $request->rate_id,
                $request->amount,
                $request->payment_frequency_id
            );
            return response()->json([
                'success' => true,
                'message' => 'Exportación preparada',
                'data' => [
                    'schedule' => $schedule,
                    'format' => $request->format
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
