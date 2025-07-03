<?php

namespace App\Http\Resources\Subastas\Investment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordInvestmentResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'propiedad_nombre' => $this->property->nombre ?? 'Desconocido',
            'monto_invertido' => $this->monto_invertido,
            'fecha_inversion' => $this->fecha_inversion,
            'resultado' => 'En espera',
        ];
    }
}
