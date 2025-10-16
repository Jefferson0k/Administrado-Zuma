<?php

namespace App\Http\Controllers\Api;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\CreateInvestmentResquest;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;
use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Movement;
use Illuminate\Http\Request;
use App\Services\InvestmentSimulatorService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Support\Facades\Log;

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
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $investments = $investor->investments()
                ->select([
                    'investments.id',
                    'investments.amount',
                    'investments.return',
                    'investments.rate',
                    'investments.currency',
                    'investments.due_date',
                    'investments.status',
                    'investments.created_at',
                    'investments.updated_at',
                    'invoices.amount as invoice_amount',
                    'invoices.codigo as codigo',
                    'invoices.invoice_code as invoice_code',
                    'invoices.estimated_pay_date as invoice_estimated_pay_date',
                    'companies.name as company_name',
                    'investments.company_risk_at_investment as company_risk_at_investment',
                ])
                ->join('invoices', 'invoices.id', '=', 'investments.invoice_id')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->where('investments.previous_investment_id', null)
                ->withCount(['relatedInvestments as history_count'])
                ->orderBy('investments.created_at', 'desc')
                ->paginate(10)
                ->withPath('')
                ->setPageName($request->input('pageName') ?: 'page');

            return response()->json([
                'success' => true,
                'data' => $investments,
            ]);
        } catch (\Throwable $th) {
            Log::error('Error fetching investments: ' . $th->getMessage(), ['exception' => $th]);
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function showDetails(Request $request, string $id){
        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->select(
                    'investments.*',
                    'invoices.amount as invoice_amount',
                    'invoices.invoice_code as invoice_code',
                    'invoices.estimated_pay_date as invoice_estimated_pay_date',
                    'companies.name as company_name',
                )
                ->join('invoices', 'invoices.id', '=', 'investments.invoice_id')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->where('original_investment_id', $id)
                ->where('previous_investment_id', '!=', null)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function invest(CreateInvestmentResquest $request){
        $validatedData = $request->validated();

        try {
            $invoice_code = $validatedData['invoice_code'];
            $currency = $validatedData['currency'];
            $amountMoney = MoneyConverter::fromDecimal($validatedData['amount'], $currency);
            $expectedReturnMoney = MoneyConverter::fromDecimal($validatedData['expected_return'], $currency);

            DB::beginTransaction();

            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();
            $invoice = Invoice::where('invoice_code', $invoice_code)->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Factura no encontrada.',
                    'data' => null,
                ], 404);
            }

            $company = $invoice->company()->first();

            // Verificar saldo disponible
            $invoiceFinancedAmountMoney = MoneyConverter::fromDecimal($invoice->financed_amount, $currency);
            if ($amountMoney->greaterThan($invoiceFinancedAmountMoney)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La inversión excede el monto disponible de la factura.',
                    'data' => null,
                ], 400);
            }

            // Crear movimiento
            $movement = new Movement();
            $movement->currency = $currency;
            $movement->amount = $amountMoney;
            $movement->type = MovementType::INVESTMENT;
            $movement->status = MovementStatus::VALID;
            $movement->confirm_status = MovementStatus::VALID;
            $movement->investor_id = $investor->id;
            $movement->save();

            // Crear inversión
            $investment = new Investment();
            $investment->currency = $invoice->currency;
            $investment->amount = $amountMoney;
            $investment->return = $expectedReturnMoney;
            $investment->rate = $invoice->rate;
            $investment->due_date = $invoice->estimated_pay_date;
            $investment->status = 'active';
            $investment->investor_id = $investor->id;
            $investment->invoice_id = $invoice->id;
            $investment->movement_id = $movement->id;
            $investment->save();

            // Actualizar balance del inversor
            $balance = $investor->getBalance($currency);
            $investorBalanceAmountMoney = MoneyConverter::fromDecimal($balance->amount, $currency);
            $investorBalanceInvestedAmountMoney = MoneyConverter::fromDecimal($balance->invested_amount, $currency);
            $investorBalanceExpectedAmountMoney = MoneyConverter::fromDecimal($balance->expected_amount ?? 0, $currency);

            $balance->amount = $investorBalanceAmountMoney->subtract($amountMoney);
            $balance->invested_amount = $investorBalanceInvestedAmountMoney->add($amountMoney);
            $balance->expected_amount = $investorBalanceExpectedAmountMoney->add($expectedReturnMoney);
            $balance->save();

            // Restar del monto pendiente en la factura
            $invoice->financed_amount = $invoiceFinancedAmountMoney->subtract($amountMoney);
            $invoice->save();

            // Notificación por correo
            $investor->sendInvestmentEmailNotification($invoice, $investment, $company);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Operación realizada con éxito.',
                'data' => null,
            ], 201);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => null,
            ], 500);
        }
    }
    public function reportTimeline(Request $request){
        $validatedData = $request->validate([
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD']),
            ],
        ]);


        $currency = $validatedData['currency'];

        try {
            /** @var \App\Models\Investor $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->select([
                    'investments.amount',
                    'investments.currency',
                    'investments.created_at'
                ])
                ->where(function ($query) {
                    $query->where('investments.status', 'active')
                        ->orWhere('investments.status', 'paid');
                })
                ->where('investments.created_at', '>=', Carbon::now()->subMonths(12))
                ->where('investments.currency', '=', $currency)
                ->where('investments.previous_investment_id', '=', null)
                ->orderBy('investments.created_at', 'desc')
                ->cursor()
                ->collect();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => "Error interno del servidor",
            ], 500);
        }
    }
    public function reportGroupByCompany(Request $request){
        $validatedData = $request->validate([
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD']),
            ],
        ]);

        $currency = $validatedData['currency'];

        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->selectRaw('
                    companies.id as company_id,
                    companies.name as company_name,
                    ROUND(SUM(investments.amount), 2) as total_amount,
                    COUNT(investments.id) as investment_count
                ')
                ->whereIn('investments.status', ['active', 'paid', 'reprogramed'])
                ->where('investments.currency', $currency)
                ->join('invoices', 'invoices.id', '=', 'investments.invoice_id')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->groupBy('companies.id', 'companies.name')
                ->orderByRaw('SUM(investments.amount) DESC')
                ->cursor()
                ->collect();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => "Error interno del servidor",
            ], 500);
        }
    }
    public function reportGroupByCompanySector(Request $request)
    {
        $validatedData = $request->validate([
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD']),
            ],
        ]);

        $currency = $validatedData['currency'];

        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->selectRaw(
                    'sectors.name as sector_name,
                    ROUND(SUM(investments.amount), 2) as total_amount,
                    COUNT(investments.id) as investment_count'
                )->whereIn('investments.status', ['active', 'paid', 'reprogramed'])
                ->where('investments.currency', '=', $currency)
                ->join('invoices', 'invoices.id', '=', 'investments.invoice_id')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->join('sectors', 'sectors.id', '=', 'companies.sector_id')
                ->groupBy('sectors.id', 'sectors.name')
                ->orderBy('total_amount', 'desc')
                ->cursor()
                ->collect();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => "Error interno del servidor",
            ], 500);
        }
    }
    public function reportCumulativeReturns(Request $request)
    {
        $validatedData = $request->validate([
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD']),
            ],
        ]);

        $currency = $validatedData['currency'];

        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('ROUND(SUM(investments.return) / 100, 2) as monthly_amount'),
                    DB::raw('ROUND(SUM(SUM(investments.return)) OVER (ORDER BY DATE_FORMAT(created_at, "%Y-%m") ASC) / 100, 2) as cumulative_amount')
                )
                ->whereIn('status', ['paid', 'reprogramed'])
                ->where('previous_investment_id', '=', null) // TODO: only original investments?
                ->where('created_at', '>=', Carbon::now()->subMonths(12))
                ->where('currency', $currency)
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->cursor()
                ->collect();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function reportCumulativeReturnsByCompany(Request $request)
    {
        $request->validate([
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD']),
            ],
        ]);

        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->select(
                    'companies.id as company_id',
                    'companies.name as company_name',
                    DB::raw('SUM(investments.return) as total_amount'),
                    DB::raw('COUNT(investments.id) as investment_count')
                )->whereIn('investments.status', ['paid', 'reprogramed']) // TODO: cambiar a paid
                ->where('investments.currency', '=', $request->currency)
                ->join('invoices', 'invoices.id', '=', 'investments.invoice_id')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->groupBy('companies.id', 'companies.name')
                ->cursor()
                ->collect();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    public function reportCumulativeReturnsBySector(Request $request)
    {
        $validatedData = $request->validate([
            'currency' => [
                'required',
                'string',
                Rule::in(['PEN', 'USD']),
            ],
        ]);

        $currency = $validatedData['currency'];

        try {
            /** @var \App\Models\User $investor */
            $investor = Auth::user();

            $data = $investor->investments()
                ->selectRaw(
                    'sectors.name as sector_name,
                    ROUND(SUM(investments.return) / 100, 2) as total_amount,
                    COUNT(investments.id) as investment_count'
                )->whereIn('investments.status', ['paid', 'reprogramed'])
                ->where('investments.currency', '=', $currency)
                ->join('invoices', 'invoices.id', '=', 'investments.invoice_id')
                ->join('companies', 'companies.id', '=', 'invoices.company_id')
                ->join('sectors', 'sectors.id', '=', 'companies.sector_id')
                ->groupBy('sectors.id', 'sectors.name')
                ->orderBy('total_amount', 'desc')
                ->cursor()
                ->collect();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
