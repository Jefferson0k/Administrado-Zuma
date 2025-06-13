<?php 

namespace App\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class FilterBySearch{
    public function __construct(private ?string $search) {}
    public function __invoke(Builder $builder, Closure $next){
        if ($this->search) {
            $builder->where(function ($query) {
                $query->where('nombre', 'like', "%{$this->search}%")
                      ->orWhere('distrito', 'like', "%{$this->search}%")
                      ->orWhere('descripcion', 'like', "%{$this->search}%");
            });
        }
        return $next($builder);
    }
}
