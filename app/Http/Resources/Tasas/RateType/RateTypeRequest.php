<?php

namespace App\Http\Resources\Tasas\RateType;

use Illuminate\Http\Resources\Json\JsonResource;
class RateTypeRequest extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'created_at' => optional($this->created_at)->format('d/m/Y H:i') ?? 'Sin fecha',
            'updated_at' => optional($this->updated_at)->format('d/m/Y H:i') ?? 'Sin fecha',
        ];
    }
}
