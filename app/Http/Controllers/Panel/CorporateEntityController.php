<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorporateEntity\StoreCorporateEntityRequests;
use App\Http\Resources\Tasas\CorporateEntity\CorporateEntityResource;
use App\Models\CorporateEntity;
use Illuminate\Http\Request;

class CorporateEntityController extends Controller{
    public function index(){
        $corporateEntities = CorporateEntity::all();
        return CorporateEntityResource::collection($corporateEntities);
    }
    public function store(StoreCorporateEntityRequests $request){
        $validated = $request->validated();
        $corporateEntity = CorporateEntity::create($validated);
        return new CorporateEntityResource($corporateEntity);
    }
}
