<?php

namespace App\Http\Resources\Tasas\PaymentFrequency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentFrequencyResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'dias' => $this->dias,
            'created_at' => optional($this->created_at)->format('d/m/Y H:i') ?? 'Sin fecha',
            'updated_at' => optional($this->updated_at)->format('d/m/Y H:i') ?? 'Sin fecha',
        ];
    }
}
