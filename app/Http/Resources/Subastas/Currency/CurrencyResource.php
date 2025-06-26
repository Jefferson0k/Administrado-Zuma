<?php

namespace App\Http\Resources\Subastas\Currency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'codigo' => $this->codigo
        ];
    }
}
