<?php

namespace App\Http\Controllers\Panel;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\Auction\AuctionHistoryResource;
use App\Http\Resources\Subastas\Investment\InvestmentResource;
use App\Http\Resources\Subastas\Investment\RecordInvestmentResource;
use App\Models\Investment;
use Illuminate\Http\Request;
use App\Models\Auction;
use App\Models\Movement;
use App\Models\Balance;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;
class InvestmentControllers extends Controller {
    public function store(Request $request){
        $request->validate([
            'auction_id' => 'required|exists:auctions,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $investor = auth()->user();
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
    public function index($property_id){
        $inversiones = Investment::with('investors')
            ->where('property_id', $property_id)
            ->orderByDesc('monto_invertido')
            ->paginate(10);
        return InvestmentResource::collection($inversiones);
    }
    public function indexUser(Request $request){
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
}
