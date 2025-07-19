<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyReservationRequest\StorePropertyReservationRequest;
use App\Http\Resources\PropertyReservationResource;
use App\Models\Investor;
use App\Models\Property;
use App\Models\PropertyReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyReservationController extends Controller{
    public function store(StorePropertyReservationRequest $request): JsonResponse{
        try {
            DB::beginTransaction();
            $investor = auth()->user();
            $validated = $request->validated();
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
            $property = Property::lockForUpdate()->find($validated['property_id']);
            if (!$property) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Propiedad no encontrada.',
                ], 404);
            }
            if ($property->estado !== 'activa') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'La propiedad no está disponible para reserva.',
                ], 400);
            }
            $reservation = PropertyReservation::create([
                'investor_id' => $investor->id,
                'property_id' => $validated['property_id'],
                'config_id' => $validated['config_id'] ?? null,
                'amount' => $validated['amount'],
                'status' => 'pendiente',
            ]);
            $property->update(['estado' => 'espera']);
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
    public function list(){
        $investor = auth()->user();
        $pendientes = PropertyReservation::with(['property', 'config'])
            ->where('investor_id', $investor->id)
            ->pendientes()
            ->get();
        return response()->json([
            'success' => true,
            'data' => PropertyReservationResource::collection($pendientes),
        ]);
    }
    public function inversionistasConPendientes(Request $request){
        $perPage = $request->input('per_page', 15);
        $query = PropertyReservation::pendientes();
        return PropertyReservationResource::collection($query->paginate($perPage));
    }
    public function show($id){
        $reservation = PropertyReservation::with(['property', 'config', 'investor'])->find($id);
        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }
    public function update($id){
        $reservation = PropertyReservation::find($id);
        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada.',
            ], 404);
        }
        if ($reservation->status === 'cancelado') {
            return response()->json([
                'success' => false,
                'message' => 'La reserva ya está cancelada.',
            ], 400);
        }
        $reservation->update(['status' => 'cancelado']);
        if ($reservation->property) {
            $reservation->property->update(['estado' => 'activa']);
        }
        return response()->json([
            'success' => true,
            'message' => 'Reserva cancelada exitosamente.',
            'data' => $reservation,
        ]);
    }
}
