<?php

namespace App\Http\Resources\Tasas\FixedTermInvestment;

use Illuminate\Http\Resources\Json\JsonResource;

class FixedTermRateResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'valor' => $this->valor,
            'estado' => $this->estado,
            'amount_range' => [
                'id' => $this->amountRange->id,
                'desde' => $this->amountRange->desde,
                'hasta' => $this->amountRange->hasta,
                'moneda' => $this->amountRange->moneda,
            ],
            'term_plan' => [
                'id' => $this->termPlan->id,
                'nombre' => $this->termPlan->nombre,
                'dias_minimos' => $this->termPlan->dias_minimos,
                'dias_maximos' => $this->termPlan->dias_maximos,
            ],
            'rate_type' => [
                'id' => $this->rateType->id,
                'nombre' => $this->rateType->nombre,
                'descripcion' => $this->rateType->descripcion,
            ],
        ];
    }
}
