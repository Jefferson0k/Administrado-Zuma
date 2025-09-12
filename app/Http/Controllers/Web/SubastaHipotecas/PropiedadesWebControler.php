<?php

namespace App\Http\Controllers\Web\SubastaHipotecas;
use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class PropiedadesWebControler extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Property::class);
        return Inertia::render('panel/Subastas/Propiedades/indexPropiedades');
    }
}
