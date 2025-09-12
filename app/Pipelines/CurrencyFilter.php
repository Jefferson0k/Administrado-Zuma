<?php

namespace App\Pipelines;

use Closure;

class CurrencyFilter{
    protected ?string $currency;
    public function __construct(?string $currency = null){
        $this->currency = $currency;
    }
    public function handle($request, Closure $next){
        if (!empty($this->currency)) {
            $request->where('currency', $this->currency);
        }
        return $next($request);
    }
}
