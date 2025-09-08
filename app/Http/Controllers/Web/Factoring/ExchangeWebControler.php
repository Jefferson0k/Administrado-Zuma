<?php

namespace App\Http\Controllers\Web\Factoring;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeWebControler extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Exchange::class);
        return Inertia::render('panel/Factoring/Exchange/indexExchange');
    }
}
