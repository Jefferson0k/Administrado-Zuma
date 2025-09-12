<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class DepositsWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Deposit::class);
        return Inertia::render('panel/Factoring/Deposit/indexDeposit');
    }
}
