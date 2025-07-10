<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\PaymentSchedule\PaymentScheduleResource;
use App\Models\PaymentSchedule;
use App\Models\PropertyInvestor;

class PaymentScheduleController extends Controller {
    public function getCronogramaPorPropiedad($propertyId){
        $cronogramas = PaymentSchedule::whereHas('propertyInvestor', function ($query) use ($propertyId) {
            $query->where('property_id', $propertyId);
        })->paginate(10);
        return PaymentScheduleResource::collection($cronogramas);
    }
}
