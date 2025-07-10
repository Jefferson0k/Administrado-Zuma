<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tasas\TermPlan\TermPlanResource;
use App\Models\TermPlan;
use Illuminate\Http\Request;

class TermPlanController extends Controller{
    public function store(Request $request){
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'dias_minimos' => 'required|integer|min:1',
            'dias_maximos' => 'required|integer|min:1|gte:dias_minimos',
        ]);
        TermPlan::create($validated);
        return redirect()->back()->with('success', 'Plan creado con éxito');
    }
    public function list(){
        $planes = TermPlan::latest()->get();
        return TermPlanResource::collection($planes);
    }
}

