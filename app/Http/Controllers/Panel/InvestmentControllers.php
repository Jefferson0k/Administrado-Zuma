<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Helpers\MoneyConverter;
use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Auction\AuctionHistoryResource;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;
use App\Http\Resources\Subastas\Investment\InvestmentResource;
use App\Models\Investment;
use Illuminate\Http\Request;
use App\Models\Auction;
use App\Models\Movement;
use App\Models\Bid;
use App\Models\Invoice;
use App\Pipelines\CurrencyFilter;
use App\Pipelines\SearchInvestmentFilter;
use App\Pipelines\StatusFilter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InvestmentControllers extends Controller{
    public function store(Request $request)
    {
        $request->validate([
            'auction_id' => 'required|exists:auctions,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $investor = auth()->user();
        $auction = Auction::with('solicitud')->findOrFail($request->auction_id); // 👈 corregido

        $currencyMap = [
            1 => 'PEN',
            2 => 'USD',
        ];

        // 👇 ahora usamos solicitud, no property
        $currencyName = $currencyMap[$auction->solicitud->currency_id] ?? null;

        if (!$currencyName) {
            return response()->json(['message' => 'Moneda no válida.'], 400);
        }

        $amount = $request->amount;

        \Log::info('INICIANDO INVERSIÓN', [
            'investor_id' => $investor->id,
            'amount' => $amount,
            'currency' => $currencyName,
            'auction_id' => $auction->id
        ]);

        // Verificar balance directamente en la BD ANTES de crear
        $existingBalance = \DB::table('balances')
            ->where('investor_id', $investor->id)
            ->where('currency', $currencyName)
            ->first();

        \Log::info('BALANCE EXISTENTE EN BD', [
            'exists' => $existingBalance ? 'YES' : 'NO',
            'amount_raw' => $existingBalance->amount ?? 'NULL',
            'currency' => $existingBalance->currency ?? 'NULL'
        ]);

        // Si no existe balance o el amount es 0, detener inmediatamente
        if (!$existingBalance || $existingBalance->amount <= 0) {
            \Log::warning('SIN SALDO EN MONEDA SOLICITADA', [
                'currency' => $currencyName,
                'balance_amount' => $existingBalance->amount ?? 0
            ]);

            return response()->json([
                'message' => "No tienes saldo suficiente en {$currencyName} para invertir.",
                'current_balance' => $existingBalance ? ($existingBalance->amount / 100) : 0,
                'required_amount' => $amount,
                'currency' => $currencyName
            ], 400);
        }

        // Obtener el balance con Eloquent
        $balance = $investor->balances()->where('currency', $currencyName)->first();

        // Convertir el monto a objeto Money
        $amountMoney = MoneyConverter::fromDecimal($amount, $currencyName);

        \Log::info('VALIDACIÓN DE SALDO', [
            'balance_amount_subunits' => $existingBalance->amount,
            'required_amount_subunits' => $amountMoney->getAmount(),
            'balance_amount_decimal' => $existingBalance->amount / 100,
            'required_amount_decimal' => $amount,
            'currency' => $currencyName
        ]);

        // Validación directa en subunidades
        if ($existingBalance->amount < $amountMoney->getAmount()) {
            \Log::warning('SALDO INSUFICIENTE', [
                'balance_subunits' => $existingBalance->amount,
                'required_subunits' => $amountMoney->getAmount(),
                'balance_decimal' => $existingBalance->amount / 100,
                'required_decimal' => $amount
            ]);

            return response()->json([
                'message' => "Saldo insuficiente. Tienes " . ($existingBalance->amount / 100) . " {$currencyName}, necesitas {$amount} {$currencyName}.",
                'current_balance' => $existingBalance->amount / 100,
                'required_amount' => $amount,
                'currency' => $currencyName
            ], 400);
        }

        DB::beginTransaction();

        try {
            \Log::info('ACTUALIZANDO BALANCE');

            $amountSubunits = $amountMoney->getAmount();

            \Log::info('ACTUALIZACIÓN SQL', [
                'amount_restar' => $amountSubunits,
                'amount_actual' => $existingBalance->amount,
                'nuevo_amount' => $existingBalance->amount - $amountSubunits,
                'nuevo_invested' => $existingBalance->invested_amount + $amountSubunits
            ]);

            // Actualización directa con SQL
            $affected = DB::table('balances')
                ->where('id', $existingBalance->id)
                ->update([
                    'amount' => DB::raw("amount - $amountSubunits"),
                    'invested_amount' => DB::raw("invested_amount + $amountSubunits"),
                    'updated_at' => now()
                ]);

            \Log::info('RESULTADO ACTUALIZACIÓN SQL', [
                'affected_rows' => $affected,
            ]);

            if ($affected === 0) {
                throw new \Exception('No se pudo actualizar el balance');
            }

            // Crear movimiento
            $movement = Movement::create([
                'currency' => $currencyName,
                'amount' => $amount,
                'investor_id' => $investor->id,
                'type' => MovementType::INVESTMENT,
                'status' => MovementStatus::CONFIRMED,
                'confirm_status' => MovementStatus::CONFIRMED,
                'description' => 'Inversión en subasta de hipotecas',
            ]);

            \Log::info('MOVEMENT CREADO', [
                'movement_id' => $movement->id,
                'amount' => $movement->amount
            ]);

            // Buscar bid existente
            $existingBid = Bid::where('auction_id', $auction->id)
                ->where('investors_id', $investor->id)
                ->first();

            if ($existingBid) {
                $existingBid->monto += $amount;
                $existingBid->save();
                \Log::info('BID ACTUALIZADO', [
                    'bid_id' => $existingBid->id,
                    'nuevo_monto' => $existingBid->monto
                ]);
            } else {
                $newBid = Bid::create([
                    'auction_id' => $auction->id,
                    'investors_id' => $investor->id,
                    'currency' => $currencyName,
                    'monto' => $amount,
                ]);
                \Log::info('NUEVO BID CREADO', ['bid_id' => $newBid->id]);
            }

            DB::commit();
            \Log::info('TRANSACCIÓN COMMITEADA EXITOSAMENTE');

            // Obtener balance actualizado
            $finalBalance = DB::table('balances')
                ->where('id', $existingBalance->id)
                ->first();

            \Log::info('VERIFICACIÓN FINAL EN BD', [
                'amount_final' => $finalBalance->amount,
                'invested_amount_final' => $finalBalance->invested_amount
            ]);

            return response()->json([
                'message' => 'Inversión registrada exitosamente.',
                'data' => [
                    'nuevo_balance' => $finalBalance->amount / 100,
                    'nuevo_invertido' => $finalBalance->invested_amount / 100,
                    'currency' => $currencyName
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('ERROR EN INVERSIÓN', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al registrar la inversión.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index($property_id){
        $inversiones = Investment::with('investors')
            ->where('property_id', $property_id)
            ->orderByDesc('monto_invertido')
            ->paginate(10);
        return InvestmentResource::collection($inversiones);
    }
    public function indexUser(Request $request)
    {
        $investor = auth('sanctum')->user();
        if (!$investor) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        $participaciones = Bid::with([
            'subasta.property',
            'subasta.ganador'
        ])
            ->where('investors_id', $investor->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return AuctionHistoryResource::collection($participaciones);
    }
    public function show($invoice_id)
    {
        try {
            $invoice = Invoice::findOrFail($invoice_id);
            $investments = Investment::with(['investor', 'invoice'])
                ->where('invoice_id', $invoice_id)
                ->get();
            if ($investments->isNotEmpty()) {
                Gate::authorize('view', $investments->first());
            } else {
                Gate::authorize('viewAny', Investment::class);
            }
            if ($investments->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron inversiones para esta factura.',
                    'data' => []
                ], 200);
            }
            return InvestmentListResource::collection($investments);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver estas inversiones.'], 403);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Error al mostrar las inversiones.'], 500);
        }
    }
    public function indexAll(Request $request){
        try {
            Gate::authorize('viewAny', Investment::class);
            $perPage     = $request->input('per_page', 15);
            $search      = $request->input('razon_social', '');
            $currency    = $request->input('currency');
            $status      = $request->input('status');
            $codigo      = $request->input('codigo', '');
            $query = app(Pipeline::class)
                ->send(Investment::query()->with(['invoice.company', 'investor']))
                ->through([
                    new SearchInvestmentFilter($search),
                    new CurrencyFilter($currency),
                    new StatusFilter($status),
                ])
                ->thenReturn();
            $investments = $query->orderByDesc('created_at')->paginate($perPage);
            return InvestmentListResource::collection($investments)
                ->additional([
                    'total' => $investments->total(),
                ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'No tienes permiso para ver las inversiones.'
            ], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar las inversiones.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
