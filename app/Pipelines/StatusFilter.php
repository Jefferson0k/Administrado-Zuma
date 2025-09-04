<?php

namespace App\Pipelines;

use Closure;

class StatusFilter{
    protected ?string $status;
    public function __construct(?string $status = null){
        $this->status = $status;
    }
    public function handle($request, Closure $next){
        if (!empty($this->status)) {
            $request->where('status', $this->status);
        }
        return $next($request);
    }
}
