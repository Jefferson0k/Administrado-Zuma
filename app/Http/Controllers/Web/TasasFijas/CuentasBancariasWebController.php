<?php

namespace App\Http\Controllers\Web\TasasFijas;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class CuentasBancariasWebController extends Controller{
    public function views(): Response{
        //Gate::authorize('viewAny', User::class);
        return Inertia::render('panel/TasasFijas/CuentasBancarias/indexCuentasBancarias');
    }
}
