<?php

namespace App\Pipelines;

use Closure;

class SearchCompanyFilter
{
    protected $search;

    public function __construct($search)
    {
        $this->search = $search;
    }

    public function handle($request, Closure $next)
    {
        $query = $next($request);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('business_name', 'like', "%{$this->search}%")
                    ->orWhere('document', 'like', "%{$this->search}%")
                    ->orWhereHas('sector', function ($sectorQuery) {
                        $sectorQuery->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('subsector', function ($subsectorQuery) {
                        $subsectorQuery->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query;
    }
}
