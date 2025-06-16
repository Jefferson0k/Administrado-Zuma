<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\InvestmentStoreRequest;
use App\Http\Resources\Subastas\Investment\InvestmentResource;
use App\Models\Investment;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;

class InvestmentControllers extends Controller {
    public function store(InvestmentStoreRequest $request) {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado.'], 401);
        }
        $montoInvertir = $request->monto_invertido;
        if ($user->monto < $montoInvertir) {
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
        $investment = Investment::create([
            'user_id' => $user->id,
            'property_id' => $request->property_id,
            'monto_invertido' => $montoInvertir,
            'fecha_inversion' => now(),
        ]);
        $user->decrement('monto', $montoInvertir);
        return response()->json([
            'message' => 'Inversión registrada exitosamente.',
            'investment' => $investment
        ], 201);
    }
    public function index($property_id){
        $inversiones = Investment::with('usuario')
            ->where('property_id', $property_id)
            ->paginate(5);
        return InvestmentResource::collection($inversiones);
    }
}
