<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
class ExchangeWeb extends Controller{
    public function views(): Response{
        return Inertia::render('panel/Factoring/Exchange/indexExchangeWeb');
    }
}
