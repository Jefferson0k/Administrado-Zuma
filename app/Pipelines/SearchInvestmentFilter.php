<?php

namespace App\Pipelines;

use Closure;

class SearchInvestmentFilter
{
    protected string $search;

    public function __construct(?string $search)
    {
        $this->search = $search ?? '';
    }

    public function handle($query, Closure $next)
    {
        if (!empty($this->search)) {
            $query->whereHas('invoice.company', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        return $next($query);
    }
}
