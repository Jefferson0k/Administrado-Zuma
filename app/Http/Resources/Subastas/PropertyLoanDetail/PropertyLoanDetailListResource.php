<?php

namespace App\Http\Resources\Subastas\PropertyLoanDetail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyLoanDetailListResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'Dni' => $this->customer->dni ?? 'Sin dni',
            'cliente' => $this->customer->nombre . ' ' . $this->customer->apellidos,
            'propiedad' => $this->property->nombre,
            'property_id' =>$this->property_id,
            'valor' => $this->property->valor_estimado,
            'requerido' => $this->property->valor_requerido,
            'subasta' => $this->property->valor_subasta ?? 0,
            'riesgo' => $this->property->riesgo,
            'cronograma' => $this->property->tipo_cronograma,
            'dias' => $this->property->plazo->nombre,
            'estado' => $this->property->estado,
            'estado_nombre' => match ($this->property->estado) {
                'en_subasta' => 'En subasta',
                'activa' => 'Activa',
                'subastada' => 'Subastada',
                'programada' => 'Programada',
                'desactivada' => 'Desactivada',
                'adquirido' => 'Adquirido',
                'pendiente' => 'Pendiente',
                default => 'Estado desconocido',
            },
        ];
    }
}
