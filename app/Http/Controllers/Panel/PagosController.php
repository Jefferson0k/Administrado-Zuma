<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoTasa\StorePagoTasaRequest;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Http\Resources\DepositResource;
use App\Http\Resources\Subastas\PagosHipotecas\HipotecasInvestorResource;
use App\Http\Resources\Tasas\FixedTermInvestment\FixedTermInvestmentResource;
use App\Http\Resources\Tasas\Pagos\PagoTasaResource;
use App\Models\Balance;
use App\Models\Deposit;
use App\Models\FixedTermInvestment;
use App\Models\FixedTermSchedule;
use App\Models\Movement;
use App\Models\PagoTasa;
use App\Models\PaymentSchedule;
use App\Models\PropertyInvestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PagosController extends Controller{
    public function pendientes(Request $request): JsonResponse{
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $query = FixedTermInvestment::with(['investor', 'termPlan', 'frequency', 'rate.corporateEntity', 'rate.rateType'])
                ->where('status', 'activo');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('investor', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                            ->orWhere('first_last_name', 'like', "%$search%")
                            ->orWhere('second_last_name', 'like', "%$search%")
                            ->orWhere('document', 'like', "%$search%");
                    })->orWhereHas('rate.corporateEntity', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%$search%")
                            ->orWhere('ruc', 'like', "%$search%");
                    });
                });
            }
            $inversiones = $query->paginate($perPage);
            return response()->json([
                'data' => FixedTermInvestmentResource::collection($inversiones),
                'pagination' => [
                    'total' => $inversiones->total(),
                    'current_page' => $inversiones->currentPage(),
                    'per_page' => $inversiones->perPage(),
                    'last_page' => $inversiones->lastPage(),
                    'from' => $inversiones->firstItem(),
                    'to' => $inversiones->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar inversiones pendientes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
        public function store(StorePagoTasaRequest $request){
            try {
                $result = DB::transaction(function () use ($request) {
                    $schedule = FixedTermSchedule::findOrFail($request->id_fixed_term_schedule);
                    if ($schedule->status !== 'pendiente') {
                        abort(422, 'La cuota ya fue pagada o está en estado inválido.');
                    }
                    $investment = FixedTermInvestment::find($schedule->fixed_term_investment_id);
                    if (!$investment) {
                        abort(422, 'No se encontró la inversión con ID: ' . $schedule->fixed_term_investment_id);
                    }
                    $investorId = $request->id_inversionista;
                    $currency = $request->moneda;
                    $monto = $request->monto;
                    if (!$investorId || !$currency || !$monto) {
                        abort(422, 'Faltan datos requeridos: inversionista, moneda o monto.');
                    }
                    if ((float) $monto <= 0) {
                        abort(422, 'El monto debe ser mayor a cero.');
                    }
                    if ($investment->investor_id !== $investorId) {
                        abort(422, 'El inversionista no coincide con la inversión.');
                    }
                    $data = $request->validated();
                    $data['id'] = (string) Str::ulid();
                    PagoTasa::create($data);
                    $movement = Movement::create([
                        'amount' => $monto,
                        'type' => MovementType::FIXED_RATE_INTEREST_PAYMENT->value,
                        'currency' => $currency,
                        'status' => MovementStatus::CONFIRMED->value,
                        'confirm_status' => MovementStatus::CONFIRMED->value,
                        'description' => 'Pago de intereses al inversionista',
                        'origin' => 'zuma',
                        'investor_id' => $investorId,
                    ]);
                    
                    Deposit::create([
                        'nro_operation' => 'AUTO-' . now()->timestamp,
                        'amount' => $monto,
                        'currency' => $currency,
                        'description' => 'Depósito automático por pago de intereses',
                        'investor_id' => $investorId,
                        'movement_id' => $movement->id,
                        'payment_source' => 'ZUMA',
                        'type' => 'intereses',
                        'fixed_term_investment_id' => $investment->id,
                    ]);
                    $schedule->update(['status' => 'pagado']);
                    $pendingSchedules = FixedTermSchedule::where('fixed_term_investment_id', $investment->id)
                        ->where('status', 'pendiente')
                        ->count();
                        
                    if ($pendingSchedules === 0) {
                        $investment->update(['status' => 'finalizado']);
                    }
                    $balance = Balance::firstOrCreate([
                        'investor_id' => $investorId,
                        'currency' => $currency,
                    ]);
                    $balance->increment('amount', (float) $monto);
                    return ['message' => 'Pago de tasa registrado correctamente.'];
                });
                
                return response()->json($result);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Error al procesar el pago: ' . $e->getMessage()
                ], 500);
            }
        }
    public function lis(Request $request){
        $perPage = $request->get('per_page', 10);
        $pagos = PagoTasa::with(['fixedTermSchedule', 'inversionista'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        return PagoTasaResource::collection($pagos);
    }
    public function PagosHipotecas(Request $request): JsonResponse{
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $query = PropertyInvestor::with(['investor', 'property'])
                ->where('status', 'pendiente');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('investor', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                            ->orWhere('first_last_name', 'like', "%$search%")
                            ->orWhere('second_last_name', 'like', "%$search%")
                            ->orWhere('document', 'like', "%$search%");
                    })->orWhereHas('property', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%$search%")
                            ->orWhere('distrito', 'like', "%$search%")
                            ->orWhere('direccion', 'like', "%$search%");
                    });
                });
            }
            $data = $query->paginate($perPage);
            return response()->json([
                'data' => HipotecasInvestorResource::collection($data),
                'pagination' => [
                    'total' => $data->total(),
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar hipotecas pendientes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function storePayment(StorePaymentRequest $request){
        try {
            $result = DB::transaction(function () use ($request) {
                $schedule = PaymentSchedule::findOrFail($request->id_payment_schedule);

                if ($schedule->estado !== 'pendiente') {
                    abort(422, 'La cuota ya fue pagada o está en estado inválido.');
                }

                $propertyInvestor = PropertyInvestor::findOrFail($schedule->property_investor_id);

                $investorId = $request->id_inversionista;
                $currency = $request->moneda;
                $monto = $request->monto;

                if (!$investorId || !$currency || !$monto) {
                    abort(422, 'Faltan datos requeridos: inversionista, moneda o monto.');
                }

                if ((float) $monto <= 0) {
                    abort(422, 'El monto debe ser mayor a cero.');
                }

                if ((string) $propertyInvestor->investor_id !== (string) $investorId) {
                    abort(422, 'El inversionista no coincide con el registro property_investor.');
                }

                $movement = Movement::create([
                    'amount' => $monto,
                    'type' => MovementType::FIXED_RATE_INTEREST_PAYMENT->value,
                    'currency' => $currency,
                    'status' => MovementStatus::CONFIRMED->value,
                    'confirm_status' => MovementStatus::CONFIRMED->value,
                    'description' => 'Pago de intereses al inversionista',
                    'origin' => 'HPagada',
                    'investor_id' => $investorId,
                ]);

                Deposit::create([
                    'nro_operation' => 'AUTO-' . now()->timestamp,
                    'amount' => $monto,
                    'currency' => $currency,
                    'description' => 'Depósito automático por pago de intereses',
                    'investor_id' => $investorId,
                    'movement_id' => $movement->id,
                    'payment_source' => 'ZUMA',
                    'type' => 'HPagada',
                    'payment_schedules_id' => $schedule->id,
                ]);


                $schedule->update(['estado' => 'pagado']);

                $pending = PaymentSchedule::where('property_investor_id', $propertyInvestor->id)
                    ->where('estado', 'pendiente')
                    ->count();

                if ($pending === 0) {
                    $propertyInvestor->update(['status' => 'finalizado']);
                }

                // 10. Actualizar balance
                $balance = Balance::firstOrCreate([
                    'investor_id' => $investorId,
                    'currency' => $currency,
                ]);
                $balance->increment('amount', (float) $monto);

                return ['message' => 'Pago de cuota registrado correctamente.'];
            });

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function listHistorial(Request $request){
        $perPage = $request->input('per_page', 15);
        $query = Deposit::with(['investor', 'movement'])
            ->where('type', 'HPagada')
            ->orderBy('created_at', 'desc');
        return DepositResource::collection($query->paginate($perPage));
    }
}
