<?php

namespace App\Http\Resources\Subastas\Deadlines;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeadlinesResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'duracion_meses' => $this->duracion_meses
        ];
    }
}
