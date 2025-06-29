<?php

namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class SubastasOnlineWebController extends Controller{
    public function views(): Response{
        //Gate::authorize('viewAny', User::class);
        return Inertia::render('panel/Onlien/indexCuentasBancarias');
    }
    public function viewsTC(): Response{
        //Gate::authorize('viewAny', User::class);
        return Inertia::render('panel/TC/index');
    }
}
