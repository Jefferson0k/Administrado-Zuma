<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subastas\PaymentSchedule\PaymentScheduleResource;
use App\Models\PropertyInvestor;

class PaymentScheduleController extends Controller {
    
    public function index($propertyInvestorId){
        $propertyInvestor = PropertyInvestor::with('paymentSchedules')->find($propertyInvestorId);
        if (!$propertyInvestor) {
            return response()->json(['message' => 'PropertyInvestor not found'], 404);
        }
        return PaymentScheduleResource::collection($propertyInvestor->paymentSchedules);
    }
}
