<?php

namespace App\Http\Controllers\Web\TasasFijas;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class TermPlanWebController extends Controller{
    public function views(): Response{
        return Inertia::render('panel/TasasFijas/TermPlan/indexTermPlan');
    }
}
