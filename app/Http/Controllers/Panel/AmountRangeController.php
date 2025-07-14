<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\AmountRange\StoreAmountRangeRequest;
use App\Models\AmountRange;
use App\Http\Resources\Tasas\AmountRange\AmountRangeResource;

class AmountRangeController extends Controller{
    public function show($empresaId){
        $rangos = AmountRange::where('corporate_entity_id', $empresaId)->get();
        return AmountRangeResource::collection($rangos);
    }
    public function store(StoreAmountRangeRequest $request){
        $data = $request->validated();
        $rango = AmountRange::create($data);
        return response()->json([
            'message' => 'Rango creado correctamente',
            'data' => new AmountRangeResource($rango)
        ]);
    }
    public function delete($id){
        $rango = AmountRange::findOrFail($id);
        $rango->delete();
        return response()->json(['message' => 'Rango eliminado correctamente']);
    }
}
