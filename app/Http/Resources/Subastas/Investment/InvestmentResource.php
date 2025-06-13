<?php

namespace App\Http\Resources\Subastas\Investment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
        ];
    }
}