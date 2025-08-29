<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class PaymentsWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Payment::class);
        return Inertia::render('panel/Factoring/Payments/indexPaymentsWeb');
    }
}
