<?php

namespace App\Http\Controllers\Web\TasasFijas;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class RateTypeWebController extends Controller{
    public function views(): Response{
        return Inertia::render('panel/TasasFijas/RateType/indexRateType');
    }
}
