<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class InvoiceWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Invoice::class);
        return Inertia::render('panel/Factoring/Invoice/indexInvoice');
    }
}
