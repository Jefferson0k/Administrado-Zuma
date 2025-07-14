<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReglasResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'estado' => $this->estado,
            'estado_nombre' => match ($this->estado) {
                'en_subasta' => 'En subasta',
                'activa' => 'Activa',
                'subastada' => 'Subastada',
                'programada' => 'Programada',
                'desactivada' => 'Desactivada',
                'adquirido' => 'Adquirido',
                'pendiente' => 'Pendiente',
                default => 'Estado desconocido',
            },
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,
            'deadlines_id' => $this->plazo->nombre
        ];
    }
}
