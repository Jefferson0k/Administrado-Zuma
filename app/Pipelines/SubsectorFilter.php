<?php

namespace App\Pipelines;

use Closure;

class SubsectorFilter{
    protected $subsectorId;
    public function __construct($subsectorId){
        $this->subsectorId = $subsectorId;
    }
    public function handle($query, Closure $next){
        if (!empty($this->subsectorId)) {
            $ids = is_array($this->subsectorId)
                ? $this->subsectorId
                : array_filter(array_map('trim', explode(',', (string) $this->subsectorId)));
            if (count($ids) === 1) {
                $query->where('subsector_id', $ids[0]);
            } elseif (count($ids) > 1) {
                $query->whereIn('subsector_id', $ids);
            }
        }
        return $next($query);
    }
}
