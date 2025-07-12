<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PropertyLoanDetail;
use Illuminate\Http\Request;
use App\Http\Requests\PropertyLoanDetail\StorePropertyLoanDetailRequests;
use App\Http\Resources\Subastas\PropertyLoanDetail\PropertyLoanDetailListResource;
use App\Http\Resources\Subastas\PropertyLoanDetail\PropertyLoanDetailResource;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PropertyLoanDetailController extends Controller{
    public function index(Request $request){
        $perPage = $request->input('per_page', 10);
        $loanDetails = PropertyLoanDetail::with('customer')
            ->latest()
            ->paginate($perPage);
        return PropertyLoanDetailListResource::collection($loanDetails);
    }
    public function store(StorePropertyLoanDetailRequests $request){
        $validated = $request->validated();
        $loanDetail = PropertyLoanDetail::updateOrCreate(
            ['property_id' => $validated['property_id']],
            $validated
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Información del financiamiento guardada correctamente.',
            'data' => new PropertyLoanDetailResource($loanDetail),
        ]);
    }
    public function show($id){
        $loanDetail = PropertyLoanDetail::with([
            'customer',
            'property.images',
            'property.plazo',
            'property.paymentSchedules',
        ])->findOrFail($id);

        return new PropertyLoanDetailResource($loanDetail);
    }

    public function update(StorePropertyLoanDetailRequests $request, $id){
        $validated = $request->validated();
        $loanDetail = PropertyLoanDetail::findOrFail($id);
        $loanDetail->update($validated);
        return response()->json([
            'status' => 'success',
            'message' => 'Información actualizada correctamente.',
            'data' => new PropertyLoanDetailResource($loanDetail),
        ]);
    }
    public function destroy($id){
        $loanDetail = PropertyLoanDetail::findOrFail($id);
        $loanDetail->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Registro eliminado correctamente.',
        ]);
    }
    public function activacion(Request $request, $id){
        try {
            $property = Property::findOrFail($id);
            $nuevoEstado = 'en_subasta';

            $diaSubasta = $request->input('dia_subasta');
            $horaInicio = $request->input('hora_inicio');
            $horaFin = $request->input('hora_fin');

            $fechaInicio = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaInicio");
            $fechaFin = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaFin");

            if ($fechaFin->lessThanOrEqualTo($fechaInicio)) {
                return response()->json([
                    'message' => 'La hora de fin debe ser mayor a la de inicio'
                ], 422);
            }

            if (!$property->subasta) {
                $property->subasta()->create([
                    'monto_inicial' => $request->input('monto_inicial'),
                    'dia_subasta' => $diaSubasta,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'tiempo_finalizacion' => $fechaFin,
                    'estado' => 'activa',
                ]);
            }

            $property->estado = $nuevoEstado;
            $property->valor_subasta = $request->input('monto_inicial');
            $property->save();

            return response()->json([
                'message' => 'Propiedad activada en subasta correctamente.',
                'property' => $property,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al activar propiedad en subasta: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
