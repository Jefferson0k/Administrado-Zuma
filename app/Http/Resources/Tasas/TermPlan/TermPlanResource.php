<?php

namespace App\Http\Resources\Tasas\TermPlan;

use Illuminate\Http\Resources\Json\JsonResource;
class TermPlanResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'dias_minimos' => $this->dias_minimos,
            'dias_maximos' => $this->dias_maximos,
        ];
    }
}
