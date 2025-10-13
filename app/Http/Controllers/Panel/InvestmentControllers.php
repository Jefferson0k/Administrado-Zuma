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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InvestmentControllers extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'auction_id' => 'required|exists:auctions,id',
        ]);
        $investor = Auth::user();
        $auction = Auction::findOrFail($request->auction_id);
        $existingBid = Bid::where('auction_id', $auction->id)
            ->where('investors_id', $investor->id)
            ->first();
        if ($existingBid) {
            Log::info('BID YA EXISTE EN AUCTION', [
                'bid_id' => $existingBid->id,
                'auction_id' => $auction->id,
                'investor_id' => $investor->id,
            ]);
            return response()->json([
                'message' => 'Ya tienes una oferta registrada en esta subasta.',
                'data' => $existingBid,
            ], 200);
        }
        $newBid = Bid::create([
            'auction_id' => $auction->id,
            'investors_id' => $investor->id,
            'type' => 'auction',
        ]);
        Log::info('NUEVO BID REGISTRADO EN AUCTION', [
            'bid_id' => $newBid->id,
            'auction_id' => $auction->id,
            'investor_id' => $investor->id,
        ]);
        return response()->json([
            'message' => 'Bid registrado correctamente en la subasta.',
            'data' => $newBid,
        ], 201);
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

            $perPage  = $request->input('per_page', 15);
            $search   = $request->input('razon_social', '');
            $currency = $request->input('currency');
            $status   = $request->input('status');
            $codigo   = $request->input('codigo', '');

            $query = app(Pipeline::class)
                ->send(Investment::query()->with(['invoice.company', 'investor']))
                ->through([
                    new SearchInvestmentFilter($search),
                    new CurrencyFilter($currency),
                    new StatusFilter($status),
                ])
                ->thenReturn();

            $investments = $query
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $request->input('page', 1))
                ->appends($request->query());

            // ✅ include meta and links to match Laravel paginator format
            // ✅ include meta and links to match Laravel paginator format
            return response()->json([
                'data' => InvestmentListResource::collection($investments->items())->resolve(), // <-- plain array
                'meta' => [
                    'current_page' => $investments->currentPage(),
                    'last_page'    => $investments->lastPage(),
                    'per_page'     => $investments->perPage(),
                    'total'        => $investments->total(),
                ],
                'links' => [
                    'first' => $investments->url(1),
                    'last'  => $investments->url($investments->lastPage()),
                    'prev'  => $investments->previousPageUrl(),
                    'next'  => $investments->nextPageUrl(),
                ],
            ]);
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
