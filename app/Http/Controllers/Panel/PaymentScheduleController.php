<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\PaymentSchedule\PaymentScheduleResource;
use App\Models\PaymentSchedule;
use App\Models\PropertyInvestor;

class PaymentScheduleController extends Controller {
    public function getCronogramaPorPropiedad($propertyInvestorId){
        $cronogramas = PaymentSchedule::where('property_investor_id', $propertyInvestorId)
            ->paginate(10);
        return PaymentScheduleResource::collection($cronogramas);
    }

}
