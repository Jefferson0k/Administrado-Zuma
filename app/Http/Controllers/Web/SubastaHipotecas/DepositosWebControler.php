<?php

namespace App\Http\Controllers\Web\SubastaHipotecas;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class DepositosWebControler extends Controller{
    public function views(): Response{
        //Gate::authorize('viewAny', User::class);
        return Inertia::render('panel/Subastas/Depositos/indexDepositos');
    }
}
