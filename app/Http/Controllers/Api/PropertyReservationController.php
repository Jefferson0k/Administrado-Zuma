<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyReservations\PropertyReservationRequest\StorePropertyReservationRequest;
use App\Models\Property;
use App\Models\PropertyReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PropertyReservationController extends Controller{
    public function store(StorePropertyReservationRequest $request): JsonResponse{
        try {
            DB::beginTransaction();
            $investor = Auth::user();
            $validated = $request->validated();
            $reservation = PropertyReservation::create([
                'investor_id' => $investor->id,
                'property_id' => $validated['property_id'],
                'config_id' => $validated['config_id'] ?? null,
                'amount' => $validated['amount'],
                'status' => 'pendiente',
            ]);
            Property::where('id', $validated['property_id'])->update([
                'status' => 'espera',
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Reserva creada y propiedad actualizada a "espera".',
                'data' => $reservation,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la reserva.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
