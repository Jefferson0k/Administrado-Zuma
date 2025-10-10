<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PropertyLoanDetail;
use Illuminate\Http\Request;
use App\Http\Requests\PropertyLoanDetail\StorePropertyLoanDetailRequests;
use App\Http\Resources\Subastas\PropertyLoanDetail\PropertyLoanDetailListResource;
use App\Http\Resources\Subastas\PropertyLoanDetail\PropertyLoanDetailResource;
use App\Models\Investor;
use App\Models\PropertyInvestor;
use App\Models\PropertyLoanDetailApproval;
use App\Models\Solicitud;
use App\Models\SolicitudBid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

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
    public function store(StorePropertyLoanDetailRequests $request){
        $validated = $request->validated();
        $validated['monto_tasacion']      = isset($validated['monto_tasacion']) ? (int) $validated['monto_tasacion'] : null;
        $validated['porcentaje_prestamo'] = isset($validated['porcentaje_prestamo']) ? (int) $validated['porcentaje_prestamo'] : null;
        $validated['monto_invertir']      = isset($validated['monto_invertir']) ? (int) $validated['monto_invertir'] : null;
        $validated['monto_prestamo']      = isset($validated['monto_prestamo']) ? (int) $validated['monto_prestamo'] : null;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $loanDetail = PropertyLoanDetail::updateOrCreate(
            ['solicitud_id' => $validated['solicitud_id']],
            $validated
        );
        $loanDetail->estado_conclusion = 'pendiente';
        $loanDetail->save();
        $propertyInvestor = PropertyInvestor::where('config_id', $validated['config_id'])->first();
        if ($propertyInvestor) {
            $propertyInvestor->investor_id = $validated['investor_id'];
            $propertyInvestor->save();
            Investor::where('id', $validated['investor_id'])
                ->update(['asignado' => 1]);
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Información del financiamiento registrada correctamente. Pendiente de aprobación.',
            'data'    => new PropertyLoanDetailResource($loanDetail),
        ]);
    }
    public function approve(Request $request, $id){
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,observed',
            'comment' => 'nullable|string|max:500',
        ]);
        $userId = Auth::id();
        DB::beginTransaction();
        try {
            $loanDetail = PropertyLoanDetail::findOrFail($id);
            $loanDetail->update([
                'estado_conclusion' => $validated['status'],
                'approval1_status'  => $validated['status'],
                'approval1_by'      => $userId,
                'approval1_comment' => $validated['comment'] ?? null,
                'approval1_at'      => now(),
                'updated_by'        => $userId,
            ]);
            PropertyLoanDetailApproval::create([
                'loan_detail_id' => $loanDetail->id,
                'status'         => $validated['status'],
                'approved_by'    => $userId,
                'comment'        => $validated['comment'] ?? null,
                'approved_at'    => now(),
            ]);
            if ($validated['status'] === 'approved') {
                $solicitud = Solicitud::findOrFail($loanDetail->solicitud_id);
                $solicitud->update(['estado' => 'activa']);
                $existeBid = SolicitudBid::where('solicitud_id', $loanDetail->solicitud_id)->exists();
                if (!$existeBid) {
                    SolicitudBid::create([
                        'solicitud_id' => $loanDetail->solicitud_id,
                        'investor_id'  => $solicitud->investor_id,
                        'estado'       => 'pendiente',
                        'created_by'   => $userId,
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Aprobación registrada correctamente.',
                'data'    => $loanDetail,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la aprobación.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id){
        $loanDetail = PropertyLoanDetail::with([
            'investor',
            'solicitud.properties.images',
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
            $solicitud = Solicitud::findOrFail($id);
            //Gate::authorize('activarSubasta', $solicitud);
            $nuevoEstado = 'en_subasta';
            $diaSubasta = $request->input('dia_subasta');
            $horaInicio = $request->input('hora_inicio');
            $horaFin = $request->input('hora_fin');
            $montoInicial = $request->input('monto_inicial');
            $fechaInicio = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaInicio");
            $fechaFin = Carbon::createFromFormat('Y-m-d H:i:s', "$diaSubasta $horaFin");
            if ($fechaFin->lessThanOrEqualTo($fechaInicio)) {
                return response()->json([
                    'message' => 'La hora de fin debe ser mayor a la de inicio'
                ], 422);
            }
            if (!$solicitud->subasta) {
                $solicitud->subasta()->create([
                    'monto_inicial'       => $montoInicial,
                    'dia_subasta'         => $diaSubasta,
                    'hora_inicio'         => $horaInicio,
                    'hora_fin'            => $horaFin,
                    'tiempo_finalizacion' => $fechaFin,
                    'estado'              => 'en_subasta',
                    'created_by'          => Auth::id(),
                ]);
            } else {
                $solicitud->subasta->update([
                    'monto_inicial'       => $montoInicial,
                    'dia_subasta'         => $diaSubasta,
                    'hora_inicio'         => $horaInicio,
                    'hora_fin'            => $horaFin,
                    'tiempo_finalizacion' => $fechaFin,
                    'estado'              => 'en_subasta',
                    'updated_by'          => Auth::id(),
                ]);
            }
            $solicitud->estado = $nuevoEstado;
            $solicitud->updated_by = Auth::id();
            $solicitud->save();
            return response()->json([
                'message'   => 'Solicitud activada en subasta correctamente.',
                'solicitud' => $solicitud->load('subasta'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al activar solicitud en subasta: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
