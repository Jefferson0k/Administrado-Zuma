<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class CompanyWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', Company::class);
        return Inertia::render('panel/Factoring/Company/indexCompany');
    }
}
