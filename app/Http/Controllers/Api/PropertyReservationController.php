<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyReservations\StorePropertyReservationRequest;
use App\Models\Property;
use App\Models\PropertyReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PropertyReservationController extends Controller
{
    public function store(StorePropertyReservationRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $investor = Auth::user();
            $validated = $request->validated();
            
            // Verificar si ya existe una reserva activa para esta propiedad
            $existingReservation = PropertyReservation::where('investor_id', $investor->id)
                ->where('property_id', $validated['property_id'])
                ->whereIn('status', ['pendiente', 'reservado'])
                ->first();
                
            if ($existingReservation) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una reserva activa para esta propiedad.',
                ], 400);
            }
            
            // Verificar y actualizar el estado de la propiedad (con lock para evitar concurrencia)
            $property = Property::lockForUpdate()->find($validated['property_id']);
            
            if (!$property) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Propiedad no encontrada.',
                ], 404);
            }
            
            if ($property->status !== 'disponible') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'La propiedad no está disponible para reserva.',
                ], 400);
            }
            
            // Crear la reserva
            $reservation = PropertyReservation::create([
                'investor_id' => $investor->id,
                'property_id' => $validated['property_id'],
                'config_id' => $validated['config_id'] ?? null,
                'amount' => $validated['amount'],
                'status' => 'pendiente',
            ]);
            
            // Actualizar estado de la propiedad
            $property->update(['status' => 'espera']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Reserva creada exitosamente.',
                'data' => $reservation->load(['property', 'config']),
            ], 201);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la reserva.',
                'error' => config('app.debug') ? $th->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}