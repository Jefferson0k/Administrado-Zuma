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
        $investor = $this->investor;

        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'investor_id' => $this->investor_id,
            'ocupacion_profesion' => $this->ocupacion_profesion,
            'motivo_prestamo' => $this->motivo_prestamo,
            'descripcion_financiamiento' => $this->descripcion_financiamiento,
            'solicitud_prestamo_para' => $this->solicitud_prestamo_para,
            'garantia' => $this->garantia,
            'perfil_riesgo' => $this->perfil_riesgo,
            'empresa_tasadora' => $this->empresa_tasadora,
            'config_id' => $this->config_id,

            // Datos relacionados
            'documento' => $investor?->document ?? 'Sin documento',
            'cliente' => $investor ? "{$investor->name} {$investor->first_last_name} {$investor->second_last_name}" : 'Sin nombre',
            'propiedad' => $property?->nombre,
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
                'espera' => 'Espera',
                default => 'Estado desconocido',
            },
        ];
    }
}
