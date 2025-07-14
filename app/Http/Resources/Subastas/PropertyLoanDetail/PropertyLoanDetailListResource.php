<?php

namespace App\Http\Resources\Subastas\PropertyLoanDetail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyLoanDetailListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $property = $this->property;
        $config = $property->ultimaConfiguracion;

        return [
            'id' => $this->id,
            'Dni' => $this->customer->dni ?? 'Sin dni',
            'cliente' => optional($this->customer)->nombre . ' ' . optional($this->customer)->apellidos,
            'propiedad' => optional($property)->nombre,
            'property_id' => $property?->id,
            'valor' => $property?->valor_estimado,
            'requerido' => $property?->valor_requerido,
            'subasta' => $property?->valor_subasta ?? 0,
            'riesgo' => $config?->riesgo ?? 'No asignado',
            'cronograma' => $config?->tipo_cronograma ?? 'No definido',
            'plazo' => $config?->plazo?->nombre ?? 'Sin plazo asignado',
            'estado' => $property?->estado,
            'estado_nombre' => match ($property?->estado) {
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
