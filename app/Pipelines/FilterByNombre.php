<?php

namespace App\Pipelines;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class FilterByNombre{
    public function __construct(private ?string $nombre) {}
    public function __invoke(Builder $builder, Closure $next){
        if (!$this->nombre) {
            return $next($builder);
        }
        $builder->whereLike('nombre', "%$this->nombre%");
        return $next($builder);
    }
}