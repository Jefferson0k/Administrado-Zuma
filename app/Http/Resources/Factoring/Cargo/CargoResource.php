<?php

namespace App\Http\Resources\Factoring\Cargo;

use Illuminate\Http\Resources\Json\JsonResource;
class CargoResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
