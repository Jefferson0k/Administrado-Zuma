<?php

namespace App\Pipelines;

use Closure;

class RateRangeFilter{
    protected $min;
    protected $max;
    public function __construct($min = null, $max = null){
        $this->min = $min;
        $this->max = $max;
    }
    public function handle($request, Closure $next){
        if ($this->min !== null) {
            $request->where('rate', '>=', $this->min);
        }
        if ($this->max !== null) {
            $request->where('rate', '<=', $this->max);
        }
        return $next($request);
    }
}
