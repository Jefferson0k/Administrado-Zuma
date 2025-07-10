<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReglaResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'tipo_cronograma' => $this->tipo_cronograma,
            'deadlines_id' =>$this->deadlines_id,
            'estado' => $this->estado,
            'riesgo' => $this->riesgo,
        ];
    }
}
