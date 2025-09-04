<?php

namespace App\Pipelines;

use Closure;

class SectorFilter{
    protected $sectorId;
    public function __construct($sectorId){
        $this->sectorId = $sectorId;
    }
    public function handle($query, Closure $next){
        if (!empty($this->sectorId)) {
            $ids = is_array($this->sectorId)
                ? $this->sectorId
                : array_filter(array_map('trim', explode(',', (string) $this->sectorId)));
            if (count($ids) === 1) {
                $query->where('sector_id', $ids[0]);
            } elseif (count($ids) > 1) {
                $query->whereIn('sector_id', $ids);
            }
        }
        return $next($query);
    }
}
