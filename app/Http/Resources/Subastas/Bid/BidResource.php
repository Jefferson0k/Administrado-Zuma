<?php

namespace App\Http\Resources\Subastas\Bid;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'alias' => $this->usuario->usuario,
            'monto' => $this->monto,
            'created_at' => $this->created_at,
        ];
    }
}