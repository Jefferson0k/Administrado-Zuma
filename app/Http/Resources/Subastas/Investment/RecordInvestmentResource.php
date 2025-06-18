<?php

namespace App\Http\Resources\Subastas\Investment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordInvestmentResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'user' => $this->user_id,
            'propiedad_nombre' => $this->propiedad->nombre ?? 'Desconocido',
            'monto_invertido' => $this->monto_invertido,
            'fecha_inversion' => $this->fecha_inversion,
            'resultado' => $this->subasta && $this->subasta->ganador_id
                ? ($this->subasta->ganador_id == $this->user_id ? 'Ganó' : 'Perdió')
                : 'En espera',
        ];
    }
}
