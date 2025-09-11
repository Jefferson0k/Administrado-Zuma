<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
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

class InvestmentControllers extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'auction_id' => 'required|exists:auctions,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $investor = Auth::user();
        $auction = Auction::with('property')->findOrFail($request->auction_id);

        $currencyMap = [
            1 => 'PEN',
            2 => 'USD',
        ];
        $currencyName = $currencyMap[$auction->property->currency_id] ?? null;

        if (!$currencyName) {
            return response()->json(['message' => 'Moneda no válida.'], 400);
        }

        $amount = $request->amount;

        $balance = $investor->balances()->where('currency', $currencyName)->first();

        if (!$balance || $balance->amount < $amount) {
            return response()->json([
                'message' => 'Saldo insuficiente para invertir en esta subasta.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $balance->amount -= $amount;
            $balance->invested_amount += $amount;

            $balance->save();

            Movement::create([
                'investor_id' => $investor->id,
                'type' => MovementType::INVESTMENT,
                'status' => MovementStatus::CONFIRMED,
                'confirm_status' => MovementStatus::CONFIRMED,
                'amount' => $amount,
                'currency' => $currencyName,
                'description' => 'Inversión en subasta de hipotecas',
            ]);

            $existingBid = Bid::where('auction_id', $auction->id)
                ->where('investors_id', $investor->id)
                ->first();

            if ($existingBid) {
                $existingBid->monto += $amount;
                $existingBid->save();
            } else {
                Bid::create([
                    'auction_id' => $auction->id,
                    'investors_id' => $investor->id,
                    'monto' => $amount,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Inversión registrada exitosamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar la inversión.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function index($property_id)
    {
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
    public function indexAll(Request $request)
    {
        try {
            Gate::authorize('viewAny', Investment::class);
<<<<<<< HEAD
            $perPage     = $request->input('per_page', 15);
            $search      = $request->input('razon_social', '');
            $currency    = $request->input('currency');
            $status      = $request->input('status');
            $codigo      = $request->input('codigo', '');
=======

            $perPage  = $request->input('per_page', 15);
            $search   = $request->input('razon_social', '');
            $currency = $request->input('currency');
            $status   = $request->input('status');

            // ---- Sorting flexible con lista blanca y fallbacks
            // Permitimos ordenar por columnas propias y por la fecha de la factura relacionada
            $allowed = [
                'created_at',         // investments.created_at
                'id',                 // investments.id
                'investment_date',    // si tu tabla lo tiene
                'amount',             // si tu tabla lo tiene (monto invertido)
                'rate',               // si tu tabla lo tiene (tasa)
                'invoice_issue_date', // ordena por invoices.issue_date
            ];

            // Default: si existe invoice.issue_date lo usamos; si no, created_at; si no, id
            $default = Schema::hasColumn('invoices', 'issue_date')
                ? 'invoice_issue_date'
                : (Schema::hasColumn('investments', 'created_at') ? 'created_at' : 'id');

            $sortBy  = $request->input('sort_by', $default);
            $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            if (!in_array($sortBy, $allowed, true)) {
                $sortBy = $default;
            }

>>>>>>> ce8d930677d48cef0d601db4b9d28889c0a799cf
            $query = app(Pipeline::class)
                ->send(Investment::query()->with(['invoice.company', 'investor']))
                ->through([
                    new SearchInvestmentFilter($search, $codigo),
                    new CurrencyFilter($currency),
                    new StatusFilter($status),
                ])
                ->thenReturn();

            // Aplicar orden:
            if ($sortBy === 'invoice_issue_date') {
                // Ordenar por la fecha de emisión de la factura relacionada
                $query->leftJoin('invoices', 'invoices.id', '=', 'investments.invoice_id')
                    ->orderBy('invoices.issue_date', $sortDir)
                    ->orderBy('investments.id', 'desc')   // desempate estable
                    ->select('investments.*');            // evita ambigüedad de columnas
            } else {
                // Mapear sortBy a la tabla investments
                $column = in_array($sortBy, ['created_at', 'id', 'investment_date', 'amount', 'rate'], true)
                    ? "investments.$sortBy"
                    : 'investments.created_at';

                // Si la columna no existe, usa fallback
                $columnExists = str_contains($column, 'investments.')
                    ? Schema::hasColumn('investments', str_replace('investments.', '', $column))
                    : true;

                if (!$columnExists) {
                    $column = Schema::hasColumn('investments', 'created_at') ? 'investments.created_at' : 'investments.id';
                }

                $query->orderBy($column, $sortDir)
                    ->orderBy('investments.id', 'desc');
            }

            $investments = $query->paginate($perPage)->appends($request->query());

            return InvestmentListResource::collection($investments)
                ->additional(['total' => $investments->total()]);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No tienes permiso para ver las inversiones.'], 403);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al listar las inversiones.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
