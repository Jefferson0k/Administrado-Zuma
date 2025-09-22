<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\PaymentSchedule\PaymentScheduleResource;
use App\Models\PaymentSchedule;
use App\Models\PropertyInvestor;
use Illuminate\Support\Facades\Auth;

class PaymentScheduleController extends Controller{
public function getCronogramaPorUsuario($property_investor_id)
{
    $investor = Auth::guard('investor')->user();

    $propertyInvestor = PropertyInvestor::where('id', $property_investor_id)
        ->where('investor_id', $investor->id)
        ->first();

    if (!$propertyInvestor) {
        return response()->json(['message' => 'No se encontró inversión para este usuario'], 404);
    }

    $cronogramas = PaymentSchedule::where('property_investor_id', $propertyInvestor->id)
        ->orderBy('vencimiento', 'asc')  // orden normal (ascendente)
        ->orderBy('id', 'asc')           // desempate
        ->get();

    return PaymentScheduleResource::collection($cronogramas);
}

    public function Cronograma($property_investor_id)
    {
        $propertyInvestor = PropertyInvestor::find($property_investor_id);
        if (!$propertyInvestor) {
            return response()->json(['message' => 'No se encontró la inversión especificada'], 404);
        }
        $cronogramas = PaymentSchedule::where('property_investor_id', $propertyInvestor->id)
            ->paginate(request('per_page', 10));
        return PaymentScheduleResource::collection($cronogramas);
    }
    public function getCronograma($property_investor_id)
    {
        $cronogramas = PaymentSchedule::where('property_investor_id', $property_investor_id)
            ->paginate(10);

        if ($cronogramas->isEmpty()) {
            return response()->json(['message' => 'No se encontró cronograma para esta inversión'], 404);
        }

        return PaymentScheduleResource::collection($cronogramas);
    }
    public function getCronogramaPorPropiedad($id)
    {
        try {
            $configId = $id;
            if (!$configId) {
                return response()->json([
                    'success' => false,
                    'message' => 'El config_id es requerido',
                    'data' => []
                ], 400);
            }
            $propertyInvestor = PropertyInvestor::where('config_id', $configId)->first();
            if (!$propertyInvestor) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información para el config_id proporcionado',
                    'data' => []
                ], 404);
            }
            $cronograma = PaymentSchedule::where('property_investor_id', $propertyInvestor->id)
                ->orderBy('cuota', 'asc')
                ->get();
            if ($cronograma->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró cronograma de pagos para esta propiedad',
                    'data' => []
                ], 404);
            }
            $cronogramaFormatted = PaymentScheduleResource::collection($cronograma);
            $totales = [
                'total_capital' => $cronograma->sum('capital'),
                'total_intereses' => $cronograma->sum('intereses'),
                'total_igv' => $cronograma->sum('igv'),
                'total_cuotas' => $cronograma->sum('total_cuota'),
                'numero_cuotas' => $cronograma->count()
            ];
            return response()->json([
                'success' => true,
                'message' => 'Cronograma obtenido exitosamente',
                'data' => [
                    'property_investor_id' => $propertyInvestor->id,
                    'config_id' => $configId,
                    'cronograma' => $cronogramaFormatted,
                    'totales' => $totales,
                    'resumen' => [
                        'monto_inicial' => $propertyInvestor->amount,
                        'estado_property_investor' => $propertyInvestor->status,
                        'total_cuotas' => $totales['numero_cuotas'],
                        'primera_cuota' => $cronograma->first()->vencimiento->format('d/m/Y'),
                        'ultima_cuota' => $cronograma->last()->vencimiento->format('d/m/Y')
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cronograma: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
