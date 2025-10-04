<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PropertyLoanDetail;
use Illuminate\Http\Request;
use App\Http\Requests\PropertyLoanDetail\StorePropertyLoanDetailRequests;
use App\Http\Resources\Subastas\PropertyLoanDetail\PropertyLoanDetailListResource;
use App\Http\Resources\Subastas\PropertyLoanDetail\PropertyLoanDetailResource;
use App\Models\Investor;
use App\Models\Property;
use App\Models\PropertyInvestor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PropertyLoanDetailController extends Controller{
    public function index(Request $request){
        $perPage = $request->input('per_page', 10);
        $loanDetails = PropertyLoanDetail::with([
            'investor',
            'solicitud.currency',
            'configuracion.plazo'
        ])->latest()->paginate($perPage);

        return PropertyLoanDetailListResource::collection($loanDetails);
    }
    public function store(StorePropertyLoanDetailRequests $request)
    {
        $validated = $request->validated();

        // Convertir a enteros los campos numéricos
        $validated['monto_tasacion']      = isset($validated['monto_tasacion']) ? (int) $validated['monto_tasacion'] : null;
        $validated['porcentaje_prestamo'] = isset($validated['porcentaje_prestamo']) ? (int) $validated['porcentaje_prestamo'] : null;
        $validated['monto_invertir']      = isset($validated['monto_invertir']) ? (int) $validated['monto_invertir'] : null;
        $validated['monto_prestamo']      = isset($validated['monto_prestamo']) ? (int) $validated['monto_prestamo'] : null;

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        // Crear o actualizar PropertyLoanDetail
        $loanDetail = PropertyLoanDetail::updateOrCreate(
            ['solicitud_id' => $validated['solicitud_id']],
            $validated
        );

        // Activar la solicitud asociada
        $solicitud = $loanDetail->solicitud;
        if ($solicitud && $solicitud->estado !== 'activa') {
            $solicitud->estado = 'activa';
            $solicitud->save();
        }

        // Actualizar PropertyInvestor y marcar al inversor como asignado
        $propertyInvestor = PropertyInvestor::where('config_id', $validated['config_id'])->first();
        if ($propertyInvestor) {
            $propertyInvestor->investor_id = $validated['investor_id'];
            $propertyInvestor->save();

            Investor::where('id', $validated['investor_id'])->update(['asignado' => 1]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Información del financiamiento guardada correctamente.',
            'data' => new PropertyLoanDetailResource($loanDetail),
        ]);
    }
    public function show($id){
    $loanDetail = PropertyLoanDetail::with([
        'investor',
        'solicitud.properties.images', // Esto ya trae todas las propiedades de la solicitud
        'solicitud.configuracionActiva.detalleInversionistaHipoteca',
    ])->findOrFail($id);
    
    $propertyInvestor = PropertyInvestor::where('solicitud_id', $loanDetail->solicitud_id)
        ->where('config_id', $loanDetail->config_id)
        ->first();

    if (!$propertyInvestor) {
        abort(404, 'No se encontró un inversionista con esta configuración.');
    }

    $loanDetail->solicitud->setRelation(
        'paymentSchedules',
        $propertyInvestor->paymentSchedules
    );

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
            Gate::authorize('activarSubasta', $property);
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
                    'monto_inicial'      => $request->input('monto_inicial'),
                    'dia_subasta'        => $diaSubasta,
                    'hora_inicio'        => $horaInicio,
                    'hora_fin'           => $horaFin,
                    'tiempo_finalizacion'=> $fechaFin,
                    'estado'             => 'en_subasta',
                    'created_by'         => Auth::id(),
                ]);
            }
            $property->estado = $nuevoEstado;
            $property->valor_subasta = $request->input('monto_inicial');
            $property->updated_by = Auth::id();
            $property->save();
            return response()->json([
                'message'  => 'Propiedad activada en subasta correctamente.',
                'property' => $property,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al activar propiedad en subasta: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
