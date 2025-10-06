<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\SolicitudDetalle\SolicitudDetalleResource;
use App\Models\Solicitud;

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
}
