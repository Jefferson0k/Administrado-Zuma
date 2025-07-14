<?php 

namespace App\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FilterByDistrito{
    public function __construct(private ?string $distrito) {}
    public function __invoke(Builder $builder, Closure $next){
        if ($this->distrito) {
            $builder->where('distrito', 'like', "%{$this->distrito}%");
        }
        return $next($builder);
    }
}
