<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\InvestmentStoreRequest;
use App\Http\Resources\Subastas\Investment\InvestmentResource;
use App\Http\Resources\Subastas\Investment\RecordInvestmentResource;
use App\Models\Investment;
use App\Models\Property;
use App\Services\InvestmentSimulatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class InvestmentControllers extends Controller {
    public function store(InvestmentStoreRequest $request){
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
    public function simulateByAmount(Request $request){
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);
        try {
            $service = new InvestmentSimulatorService();
            $data = $service->simulateByAmount($request->amount);
            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
