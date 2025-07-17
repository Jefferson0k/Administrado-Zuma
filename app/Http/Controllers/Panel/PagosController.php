<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoTasa\StorePagoTasaRequest;
use App\Http\Resources\Tasas\FixedTermInvestment\FixedTermInvestmentResource;
use App\Models\Balance;
use App\Models\Deposit;
use App\Models\FixedTermInvestment;
use App\Models\FixedTermSchedule;
use App\Models\Movement;
use App\Models\PagoTasa;
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
    public function store(StorePagoTasaRequest $request)
{
    try {
        $result = DB::transaction(function () use ($request) {
            $schedule = FixedTermSchedule::findOrFail($request->id_fixed_term_schedule);
            
            if ($schedule->status !== 'pendiente') {
                abort(422, 'La cuota ya fue pagada o está en estado inválido.');
            }
            
            // SOLUCIÓN: Buscar la inversión directamente ya que la relación falla
            $investment = FixedTermInvestment::find($schedule->fixed_term_investment_id);
            
            if (!$investment) {
                abort(422, 'No se encontró la inversión con ID: ' . $schedule->fixed_term_investment_id);
            }
            $investorId = $request->id_inversionista;
            $currency = $request->moneda;
            $monto = $request->monto;
            
            // Validaciones adicionales
            if (!$investorId || !$currency || !$monto) {
                abort(422, 'Faltan datos requeridos: inversionista, moneda o monto.');
            }
            
            // Validar que el monto sea positivo
            if ((float) $monto <= 0) {
                abort(422, 'El monto debe ser mayor a cero.');
            }
            
            // Validar que el inversionista coincida con la inversión
            if ($investment->investor_id !== $investorId) {
                abort(422, 'El inversionista no coincide con la inversión.');
            }
            
            // 1. Registrar el pago de la tasa
            $data = $request->validated();
            $data['id'] = (string) Str::ulid();
            PagoTasa::create($data);
            
            // 2. Registrar el movimiento
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
            
            // 3. Crear el depósito automático
            Deposit::create([
                // 'id' => (string) Str::ulid(), // Remover si usa auto-incremento
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
            
            // 4. Marcar la cuota como pagada
            $schedule->update(['status' => 'pagado']);
            
            // 5. Verificar si todas las cuotas han sido pagadas
            $pendingSchedules = FixedTermSchedule::where('fixed_term_investment_id', $investment->id)
                ->where('status', 'pendiente')
                ->count();
                
            if ($pendingSchedules === 0) {
                $investment->update(['status' => 'finalizado']);
            }
            
            // 6. Actualizar balance del inversionista
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
}
