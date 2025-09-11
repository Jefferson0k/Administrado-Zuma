<?php

namespace App\Http\Controllers\Web\SubastaHipotecas;
use App\Http\Controllers\Controller;
use App\Models\PropertyConfiguracion;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class ReglasWebController extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', PropertyConfiguracion::class);
        return Inertia::render('panel/Subastas/Propiedades/Desarrollo/Reglas/indexReglas');
    }
}
