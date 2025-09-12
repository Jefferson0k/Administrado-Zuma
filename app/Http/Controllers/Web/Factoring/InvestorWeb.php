<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Investor;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class InvestorWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Investor::class);
        return Inertia::render('panel/Factoring/Investor/indexInvestor');
    }
}
