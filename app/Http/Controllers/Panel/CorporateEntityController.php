<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tasas\CorporateEntity\CorporateEntityResource;
use App\Models\CorporateEntity;
use Illuminate\Http\Request;

class CorporateEntityController extends Controller{
    public function index(){
        $corporateEntities = CorporateEntity::all();
        return CorporateEntityResource::collection($corporateEntities);
    }
    public function store(Request $request){
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'required|string|max:11|unique:corporate_entities,ruc',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'tipo_entidad' => 'required|string|max:100',
            'estado' => 'required|boolean',
        ]);
        $corporateEntity = CorporateEntity::create($validated);
        return new CorporateEntityResource($corporateEntity);
    }
}
