<?php

namespace App\Pipelines;

use Closure;

class AmountRangeFilter{
    protected $min;
    protected $max;
    public function __construct($min = null, $max = null){
        $this->min = $min;
        $this->max = $max;
    }
    public function handle($request, Closure $next){
        if ($this->min !== null) {
            $request->where('amount', '>=', $this->min * 100);
        }
        if ($this->max !== null) {
            $request->where('amount', '<=', $this->max * 100);
        }
        return $next($request);
    }
}
