<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\InvestmentStoreRequest;
use App\Http\Resources\Subastas\Investment\InvestmentResource;
use App\Http\Resources\Subastas\Investment\RecordInvestmentResource;
use App\Models\Balance;
use App\Models\Investment;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class InvestmentControllers extends Controller {
    public function store(InvestmentStoreRequest $request){
        $investor = Auth::guard('investor')->user();
        if (!$investor) {
            return response()->json(['message' => 'Usuario no autenticado.'], 401);
        }

        $montoInvertir = $request->monto_invertido;
        $property = Property::findOrFail($request->property_id);

        $currencyId = (int) $property->currency_id;

        $balance = Balance::where('investor_id', $investor->id)
                        ->where('currency', $currencyId)
                        ->first();

        if (!$balance) {
            return response()->json([
                'message' => 'No tienes un balance registrado en esta moneda.',
            ], 422);
        }

        if ($balance->amount < $montoInvertir) {
            return response()->json([
                'message' => 'Fondos insuficientes. Tienes: ' . $balance->amount . ' y necesitas: ' . $montoInvertir,
            ], 422);
        }

        if ($montoInvertir < $property->valor_subasta) {
            return response()->json([
                'message' => 'El monto a invertir debe ser igual o mayor al monto solicitado de la propiedad (S/ ' . $property->valor_subasta . ').',
            ], 422);
        }

        $existingInvestment = Investment::where('investor_id', $investor->id)
                                        ->where('property_id', $property->id)
                                        ->first();

        if ($existingInvestment) {
            $existingInvestment->increment('monto_invertido', $montoInvertir);
            $existingInvestment->update(['fecha_inversion' => now()]);
            $investment = $existingInvestment->fresh();
            $message = 'Inversión actualizada exitosamente. Monto total: S/ ' . $investment->monto_invertido;
        } else {
            $investment = Investment::create([
                'investor_id' => $investor->id,
                'property_id' => $property->id,
                'monto_invertido' => $montoInvertir,
                'fecha_inversion' => now(),
            ]);
            $message = 'Inversión registrada exitosamente.';
        }

        $balance->decrement('amount', $montoInvertir);
        $balance->increment('invested_amount', $montoInvertir);

        return response()->json([
            'message' => $message,
            'investment' => $investment->fresh()
        ], 201);
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

        $inversiones = Investment::with('property')
            ->where('investor_id', $investor->id)
            ->paginate(5);

        return RecordInvestmentResource::collection($inversiones);
    }
}
