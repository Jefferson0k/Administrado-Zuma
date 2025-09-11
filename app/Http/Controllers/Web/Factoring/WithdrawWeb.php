<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class WithdrawWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Withdraw::class);
        return Inertia::render('panel/Factoring/Withdraw/indexWithdrawWeb');
    }
}
