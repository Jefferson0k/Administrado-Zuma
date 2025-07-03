<?php

namespace App\Http\Resources\Subastas\Investor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InversionResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'property' => [
                'id' => $this->property->id,
                'nombre' => $this->property->nombre,
                'departamento' => $this->property->departamento,
                'distrito' => $this->property->distrito,
                'valor_estimado' => $this->property->valor_estimado,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
