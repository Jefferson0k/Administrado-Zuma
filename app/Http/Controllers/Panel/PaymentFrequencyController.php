<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\PaymentFrequency;
use App\Http\Requests\PaymentFrequency\StorePaymentFrequencyRequests;
use App\Http\Requests\PaymentFrequency\UpdatePaymentFrequencyRequests;
use App\Http\Resources\Tasas\PaymentFrequency\PaymentFrequencyResource;

class PaymentFrequencyController extends Controller{
    public function index(){
        return PaymentFrequencyResource::collection(PaymentFrequency::all());
    }
    public function store(StorePaymentFrequencyRequests $request){
        $frequency = PaymentFrequency::create($request->validated());
        return new PaymentFrequencyResource($frequency);
    }
    public function show($id){
        $frequency = PaymentFrequency::findOrFail($id);
        return new PaymentFrequencyResource($frequency);
    }
    public function update(UpdatePaymentFrequencyRequests $request, PaymentFrequency $paymentFrequency){
        $paymentFrequency->update($request->validated());
        if ($request->wantsJson()) {
            return new PaymentFrequencyResource($paymentFrequency);
        }
        return redirect()->back()->with('success', 'Frecuencia actualizada');
    }
    public function destroy($id){
        $frequency = PaymentFrequency::findOrFail($id);
        $frequency->delete();
        return response()->json(['message' => 'Frecuencia eliminada.'], 200);
    }
}
