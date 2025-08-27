<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factoring\Cargo\CargoResource;
use App\Models\Cargo;
use Illuminate\Support\Facades\Gate;

class CargoController extends Controller{
    public function index(){
        Gate::authorize('viewAny', Cargo::class);
        $cargos = Cargo::all();
        return CargoResource::collection($cargos);
    }
}
