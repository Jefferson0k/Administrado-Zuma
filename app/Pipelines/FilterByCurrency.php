<?php

namespace App\Pipelines;

use Closure;

class FilterByCurrency
{
    protected $currencyId;

    public function __construct($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    public function handle($request, Closure $next)
    {
        if (!$this->currencyId) {
            return $next($request);
        }

        return $next($request)->whereHas('property', function ($q) {
            $q->where('currency_id', $this->currencyId);
        });
    }
}
