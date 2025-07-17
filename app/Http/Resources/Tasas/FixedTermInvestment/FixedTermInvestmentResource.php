<?php

namespace App\Http\Resources\Tasas\FixedTermInvestment;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
class FixedTermInvestmentResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'rate' => $this->rate?->valor,
            'entidad' => $this->rate->corporateEntity->nombre,
            'investor_id' => $this->investor_id,
            'nombre' => $this->investor->name.' '.$this->investor-> first_last_name.' '.$this->investor->second_last_name,
            'dni' => $this->investor->document,
            'ruc' => $this->rate->corporateEntity->ruc,
            'rate_type' => $this->rate?->rateType?->nombre,
            'frequency' => $this->frequency?->nombre,
            'term_plan' => $this->termPlan?->nombre,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-y'),
        ];
    }
}
