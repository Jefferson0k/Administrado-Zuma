<?php

namespace App\Pipelines;

use Closure;

class SearchInvestmentFilter
{
    protected string $search;
    protected string $codigo;

    public function __construct(?string $search, ?string $codigo = null)
    {
        $this->search = $search ?? '';
        $this->codigo = $codigo ?? '';
    }

    public function handle($query, Closure $next)
    {
        if (!empty($this->search)) {
            $query->whereHas('invoice.company', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->codigo)) {
            $query->whereHas('invoice', function ($q) {
                $q->where('codigo', 'like', '%' . $this->codigo . '%');
            });
        }

        return $next($query);
    }
}
