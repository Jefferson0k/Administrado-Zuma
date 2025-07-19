<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\PaymentSchedule\PaymentScheduleResource;
use App\Models\PaymentSchedule;
use App\Models\PropertyInvestor;
use Illuminate\Support\Facades\Auth;

class PaymentScheduleController extends Controller {
    public function getCronogramaPorUsuario($property_investor_id){
        $investor = Auth::guard('investor')->user();
        $propertyInvestor = PropertyInvestor::where('id', $property_investor_id)
            ->where('investor_id', $investor->id)
            ->first();
        if (!$propertyInvestor) {
            return response()->json(['message' => 'No se encontró inversión para este usuario'], 404);
        }
        $cronogramas = PaymentSchedule::where('property_investor_id', $propertyInvestor->id)
            ->paginate(10);
        return PaymentScheduleResource::collection($cronogramas);
    }
    public function Cronograma($property_investor_id){
        $propertyInvestor = PropertyInvestor::find($property_investor_id);
        if (!$propertyInvestor) {
            return response()->json(['message' => 'No se encontró la inversión especificada'], 404);
        }
        $cronogramas = PaymentSchedule::where('property_investor_id', $propertyInvestor->id)
            ->paginate(request('per_page', 10));
        return PaymentScheduleResource::collection($cronogramas);
    }
    public function getCronograma($property_investor_id){
        $cronogramas = PaymentSchedule::where('property_investor_id', $property_investor_id)
            ->paginate(10);

        if ($cronogramas->isEmpty()) {
            return response()->json(['message' => 'No se encontró cronograma para esta inversión'], 404);
        }

        return PaymentScheduleResource::collection($cronogramas);
    }
}
