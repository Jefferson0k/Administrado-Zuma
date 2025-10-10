<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\SolicitudApproval\SolicitudApprovalResource;
use App\Http\Resources\Subastas\SolicitudDetalle\SolicitudDetalleResource;
use App\Models\Solicitud;
use App\Models\SolicitudApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudController extends Controller{
    public function show($id){
        try {
            $solicitud = Solicitud::with([
                'currency',
                'investor',
                'properties.images',
                'properties.tipoInmueble',
                'configuracionSubasta.plazo',
                'configuracionSubasta.subasta',
                'configuracionSubasta.propertyInvestor',
                'configuracionSubasta.detalleInversionistaHipoteca',
                'propertyInvestors.paymentSchedules',
                'propertyInvestors.configuracion'
            ])->findOrFail($id);
            return (new SolicitudDetalleResource($solicitud))
                ->additional([
                    'success' => true,
                    'message' => 'Solicitud obtenida correctamente.'
                ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la solicitud.',
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
            ], 500);
        }
    }
    public function update(Request $request, $id){
        $validated = $request->validate([
            'approval1_status' => 'required|in:approved,rejected,observed',
            'approval1_comment' => 'nullable|string|max:500',
        ]);
        if ($validated['approval1_status'] === 'observed' && empty($validated['approval1_comment'])) {
            return response()->json([
                'success' => false,
                'message' => 'El comentario es obligatorio cuando el estado es "observed".'
            ], 422);
        }
        $solicitud = Solicitud::findOrFail($id);
        $nuevoEstado = match ($validated['approval1_status']) {
            'rejected' => 'rejected',
            'observed' => 'observed',
            default => $solicitud->estado,
        };
        $solicitud->update([
            'approval1_status'  => $validated['approval1_status'],
            'approval1_by'      => Auth::id(),
            'approval1_at'      => now(),
            'approval1_comment' => $validated['approval1_comment'] ?? null,
            'estado'            => $nuevoEstado,
        ]);
        SolicitudApproval::create([
            'solicitud_id' => $solicitud->id,
            'status'       => $validated['approval1_status'],
            'approved_by'  => Auth::id(),
            'comment'      => $validated['approval1_comment'] ?? null,
            'approved_at'  => now(),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Solicitud actualizada y registrada en el historial correctamente.',
        ]);
    }
    public function showlist($solicitudId){
        $historial = SolicitudApproval::where('solicitud_id', $solicitudId)
            ->with('usuario')
            ->orderByDesc('approved_at')
            ->get();
        return SolicitudApprovalResource::collection($historial);
    }
}
