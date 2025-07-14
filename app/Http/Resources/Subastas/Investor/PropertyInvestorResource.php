<?php

namespace App\Http\Resources\Subastas\Investor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyInvestorResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'property_id' => $this->property_id,
            'amount' => $this->amount,
            'property_name' => optional($this->property)->nombre,
        ];
    }
}
