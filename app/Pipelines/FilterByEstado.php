<?php

namespace App\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FilterByEstado{
    public function __construct(private ?string $estado) {}
    public function __invoke(Builder $builder, Closure $next){
        if ($this->estado) {
            $builder->where('estado', $this->estado);
        }
        return $next($builder);
    }
}
