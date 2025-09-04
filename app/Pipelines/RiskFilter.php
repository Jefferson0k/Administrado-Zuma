<?php

namespace App\Pipelines;

use Closure;

class RiskFilter{
    protected $risk;
    public function __construct($risk){
        $this->risk = $risk;
    }
    public function handle($request, Closure $next){
        $query = $next($request);
        if ($this->risk !== null && $this->risk !== '') {
            $query->where('risk', $this->risk);
        }
        return $query;
    }
}
