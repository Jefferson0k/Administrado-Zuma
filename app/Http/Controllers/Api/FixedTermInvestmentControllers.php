<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FixedTermInvestment;
use App\Models\FixedTermSchedule;
use App\Services\InvestmentSimulatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixedTermInvestmentControllers extends Controller{
    protected $simulatorService;
    public function __construct(InvestmentSimulatorService $simulatorService){
        $this->simulatorService = $simulatorService;
    }
    public function store(Request $request): JsonResponse{
        $request->validate([
            'fixed_term_rate_id' => 'required|integer|exists:fixed_term_rates,id',
            'term_plan_id' => 'required|integer|exists:term_plans,id',
            'payment_frequency_id' => 'required|integer|exists:payment_frequencies,id',
            'amount' => 'required|numeric|min:1'
        ]);

        try {
            $investor = Auth::guard('investor')->user();
            
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            DB::beginTransaction();

            $investment = FixedTermInvestment::create([
                'investor_id' => $investor->id,
                'fixed_term_rate_id' => $request->fixed_term_rate_id,
                'term_plan_id' => $request->term_plan_id,
                'payment_frequency_id' => $request->payment_frequency_id,
                'amount' => $request->amount,
                'status' => 'pendiente',
            ]);

            $scheduleData = $this->simulatorService->generatePaymentSchedule(
                $request->fixed_term_rate_id,
                $request->amount,
                $request->payment_frequency_id
            );

            $this->saveSchedule($investment->id, $scheduleData['cronograma']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inversión creada exitosamente',
                'data' => [
                    'investment_id' => $investment->id,
                    'schedule_data' => $scheduleData
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la inversión: ' . $e->getMessage()
            ], 500);
        }
    }
    public function storeCronograma(Request $request): JsonResponse{
        $request->validate([
            'investment_id' => 'required|integer|exists:fixed_term_investments,id'
        ]);
        try {
            $investor = Auth::guard('investor')->user();
            
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            $investment = FixedTermInvestment::where('id', $request->investment_id)
                ->where('investor_id', $investor->id)
                ->first();
            if (!$investment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inversión no encontrada o no autorizada'
                ], 404);
            }

            DB::beginTransaction();

            FixedTermSchedule::where('fixed_term_investment_id', $investment->id)->delete();

            $scheduleData = $this->simulatorService->generatePaymentSchedule(
                $investment->fixed_term_rate_id,
                $investment->amount,
                $investment->payment_frequency_id
            );

            $this->saveSchedule($investment->id, $scheduleData['cronograma']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cronograma actualizado exitosamente',
                'data' => $scheduleData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el cronograma: ' . $e->getMessage()
            ], 500);
        }
    }
    private function saveSchedule(int $investmentId, array $cronograma): void{
        foreach ($cronograma as $index => $schedule) {
            if ($schedule['es_pago']) {
                FixedTermSchedule::create([
                    'fixed_term_investment_id' => $investmentId,
                    'month' => $schedule['numero_pago'],
                    'payment_date' => $this->parseDate($schedule['fecha_pago']),
                    'days' => $schedule['dias_periodo'],
                    'base_amount' => $schedule['monto_base'],
                    'interest_amount' => $schedule['interes_bruto'],
                    'second_category_tax' => $schedule['impuesto_2da'],
                    'interest_to_deposit' => $schedule['interes_neto'],
                    'capital_return' => $schedule['devolucion_capital'],
                    'capital_balance' => $schedule['saldo_capital'],
                    'total_to_deposit' => $schedule['total_a_depositar'],
                    'status' => 'pendiente',
                ]);
            }
        }
    }
    private function parseDate($dateString): ?string{
        if (!$dateString) {
            return null;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    public function index(): JsonResponse{
        try {
            $investor = Auth::guard('investor')->user();
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            $investments = FixedTermInvestment::with([
                'rate.corporateEntity',
                'rate.termPlan',
                'frequency',
                'schedules'
            ])
            ->where('investor_id', $investor->id)
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $investments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las inversiones: ' . $e->getMessage()
            ], 500);
        }
    }
    public function show(int $id): JsonResponse {
        try {
            $investor = Auth::guard('investor')->user();
            
            if (!$investor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $investment = FixedTermInvestment::with([
                'rate.corporateEntity',
                'rate.termPlan',
                'frequency',
                'schedules' => function($query) {
                    $query->orderBy('month');
                }
            ])
            ->where('id', $id)
            ->where('investor_id', $investor->id)
            ->first();

            if (!$investment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inversión no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $investment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la inversión: ' . $e->getMessage()
            ], 500);
        }
    }
}
