<?php

namespace App\Pipelines;

use Closure;

class CompanyFilter{
    protected ?string $company;
    public function __construct(?string $company = null){
        $this->company = $company;
    }
    public function handle($request, Closure $next){
        if (!empty($this->company)) {
            $request->whereHas('company', function ($q) {
                $q->where('name', 'LIKE', "%{$this->company}%")
                ->orWhere('business_name', 'LIKE', "%{$this->company}%");
            });
        }
        return $next($request);
    }
}
