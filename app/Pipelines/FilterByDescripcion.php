<?php 

namespace App\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FilterByDescripcion{
    public function __construct(private ?string $descripcion) {}
    public function __invoke(Builder $builder, Closure $next){
        if ($this->descripcion) {
            $builder->where('descripcion', 'like', "%{$this->descripcion}%");
        }
        return $next($builder);
    }
}
