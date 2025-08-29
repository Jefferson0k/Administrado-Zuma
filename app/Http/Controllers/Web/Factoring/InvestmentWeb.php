<?php

namespace App\Http\Controllers\Web\Factoring;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentWeb extends Controller{
    public function views($invoice_id): Response{
        Gate::authorize('viewAny', Investment::class);
        $invoice = Invoice::findOrFail($invoice_id);
        return Inertia::render('panel/Factoring/Investment/indexInvestment', [
            'invoice' => $invoice
        ]);
    }
    public function viewsGeneral(): Response{
        Gate::authorize('viewAny', Investment::class);
        return Inertia::render('panel/Factoring/Investment/indexInvestmentGeneral');
    }
}