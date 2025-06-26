<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyShowResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'valor' => $this->valor_estimado,
            'currency_id' => $this->currency_id,
            'tea' => $this->tea,
            'tem' => $this->tem
        ];
    }
}
