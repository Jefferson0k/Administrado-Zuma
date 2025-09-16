<?php

namespace App\Pipelines;

use Closure;

class SearchInvoiceFilter{
    protected string $search;
    public function __construct(string $search = ''){
        $this->search = $search;
    }
    public function handle($request, Closure $next){
        if (!empty($this->search)) {
            $request->where(function ($q) {
                $q->where('invoice_number', 'LIKE', "%{$this->search}%")
                  ->orWhere('loan_number', 'LIKE', "%{$this->search}%")
                  ->orWhere('codigo', 'LIKE', "%{$this->search}%")
                  ->orWhere('RUC_client', 'LIKE', "%{$this->search}%")
                  ->orWhere('status', 'LIKE', "%{$this->search}%")
                  ->orWhereHas('company', function ($q2) {
                      $q2->where('name', 'LIKE', "%{$this->search}%")
                         ->orWhere('business_name', 'LIKE', "%{$this->search}%");
                  });
            });
        }
        return $next($request);
    }
}
