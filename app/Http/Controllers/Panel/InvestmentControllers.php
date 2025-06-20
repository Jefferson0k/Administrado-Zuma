<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\InvestmentStoreRequest;
use App\Http\Resources\Subastas\Investment\InvestmentResource;
use App\Http\Resources\Subastas\Investment\RecordInvestmentResource;
use App\Models\Investment;
use App\Models\Property;
use App\Services\SimpleInvestmentCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class InvestmentControllers extends Controller {
    public function store(InvestmentStoreRequest $request)
    {
        // Usar el guard de customer
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return response()->json(['message' => 'Usuario no autenticado.'], 401);
        }

        $montoInvertir = $request->monto_invertido;

        if ($customer->monto < $montoInvertir) {
            return response()->json([
                'message' => 'Fondos insuficientes para realizar la inversión.',
            ], 422);
        }

        $property = Property::findOrFail($request->property_id);

        if ($montoInvertir < $property->monto) {
            return response()->json([
                'message' => 'El monto a invertir debe ser igual o mayor al monto solicitado de la propiedad (S/ ' . $property->monto . ').',
            ], 422);
        }

        // Buscar inversión previa del mismo customer
        $existingInvestment = Investment::where('customer_id', $customer->id)
                                        ->where('property_id', $request->property_id)
                                        ->first();

        if ($existingInvestment) {
            $existingInvestment->increment('monto_invertido', $montoInvertir);
            $existingInvestment->update(['fecha_inversion' => now()]);

            $investment = $existingInvestment->fresh();
            $message = 'Inversión actualizada exitosamente. Monto total: S/ ' . $investment->monto_invertido;
        } else {
            $investment = Investment::create([
                'customer_id' => $customer->id,
                'property_id' => $request->property_id,
                'monto_invertido' => $montoInvertir,
                'fecha_inversion' => now(),
            ]);

            $message = 'Inversión registrada exitosamente.';
        }

        // Descontar saldo del cliente
        $customer->decrement('monto', $montoInvertir);

        return response()->json([
            'message' => $message,
            'investment' => $investment->fresh()
        ], 201);
    }
    public function index($property_id){
        $inversiones = Investment::with('customer')
            ->where('property_id', $property_id)
            ->orderByDesc('monto_invertido')
            ->paginate(10);
        return InvestmentResource::collection($inversiones);
    }
    public function indexUser(Request $request){
        $user = 1;
        $inversiones = Investment::with('usuario')
            ->where('user_id', $user)
            ->paginate(5);
        return RecordInvestmentResource::collection($inversiones);
    }
    public function calculate(Request $request){
        $request->validate([
            'corporate_entity_id' => 'required|integer',
            'amount' => 'required|numeric',
            'days' => 'required|integer',
            'payment_frequency_id' => 'required|integer|exists:payment_frequencies,id'
        ]);

        try {
            $calculator = new SimpleInvestmentCalculator();
            $result = $calculator->calculate(
                $request->corporate_entity_id,
                $request->amount,
                $request->days,
                $request->payment_frequency_id
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
