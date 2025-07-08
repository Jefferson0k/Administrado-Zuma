<?php

namespace App\Http\Resources\Tasas\FixedTermInvestment;

use Illuminate\Http\Resources\Json\JsonResource;
class FixedTermInvestmentResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'rate' => $this->rate?->valor,
            'rate_type' => $this->rate?->rateType?->nombre,
            'frequency' => $this->frequency?->nombre,
            'term_plan' => $this->termPlan?->nombre,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
