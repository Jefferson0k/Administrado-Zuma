<?php

namespace App\Http\Resources\Subastas\Investment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'user' => $this->usuario->username,
            'monto_invertido' => $this->monto_invertido,
            'fecha_inversion' => $this->fecha_inversion,
        ];
    }
}