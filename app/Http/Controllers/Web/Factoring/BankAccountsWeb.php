<?php

namespace App\Http\Controllers\Web\Factoring;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class BankAccountsWeb extends Controller{
    public function views(): Response{
        Gate::authorize('viewAny', BankAccount::class);
        return Inertia::render('panel/Factoring/BankAccounts/indexBankAccounts');
    }
}
