<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\InvestmentStoreRequest;
use App\Models\Investment;
use Illuminate\Support\Facades\Auth;

class InvestmentControllers extends Controller{
    public function store(InvestmentStoreRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado.'], 401);
        }

        $montoInvertir = $request->monto_invertido;

        // Verificamos si tiene fondos suficientes
        if ($user->monto < $montoInvertir) {
            return response()->json([
                'message' => 'Fondos insuficientes para realizar la inversión.',
            ], 422);
        }

        // Verificamos que el monto sea mayor o igual al solicitado en la propiedad
        $property = \App\Models\Property::findOrFail($request->property_id);

        if ($montoInvertir < $property->monto) {
            return response()->json([
                'message' => 'El monto a invertir debe ser igual o mayor al monto solicitado de la propiedad (S/ ' . $property->monto . ').',
            ], 422);
        }

        // Crear inversión
        $investment = Investment::create([
            'user_id' => $user->id,
            'property_id' => $request->property_id,
            'monto_invertido' => $montoInvertir,
            'fecha_inversion' => now(),
        ]);

        // Descontar al usuario
        $user->decrement('monto', $montoInvertir);

        return response()->json([
            'message' => 'Inversión registrada exitosamente.',
            'investment' => $investment
        ], 201);
    }
}