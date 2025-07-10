<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ratetype\StoreRatetypeResquest;
use App\Http\Requests\Ratetype\UpdateRatetypeResquest;
use App\Http\Resources\Tasas\RateType\RateTypeRequest;
use App\Models\RateType;

class RateTypeController extends Controller{
    public function list(){
        $rateTypes = RateType::latest()->get();
        return RateTypeRequest::collection($rateTypes);
    }
    public function store(StoreRatetypeResquest $request){
        $rateType = RateType::create($request->validated());
        return response()->json([
            'message' => 'Tipo de tasa creado correctamente.',
            'data' => new RateTypeRequest($rateType)
        ]);
    }
    public function show(RateType $rateType){
        return new RateTypeRequest($rateType);
    }
    public function update(UpdateRatetypeResquest $request, RateType $rateType){
        $rateType->update($request->validated());
        return response()->json([
            'message' => 'Tipo de tasa actualizado correctamente.',
            'data' => new RateTypeRequest($rateType)
        ]);
    }
    public function destroy(RateType $rateType){
        $rateType->delete();
        return response()->json([
            'message' => 'Tipo de tasa eliminado correctamente.'
        ]);
    }
}

