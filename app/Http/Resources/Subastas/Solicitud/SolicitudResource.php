<?php

namespace App\Http\Resources\Subastas\Solicitud;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // ✅ Obtiene la primera configuración que viene filtrada (estado = 2)
        $config = $this->configuraciones->first();

        return [
            'id'                => $this->id,
            'codigo'            => $this->codigo,
            'valor_general'     => $this->valor_general 
                                    ? $this->valor_general->getAmount() / 100 
                                    : null,
            'valor_requerido'   => $this->valor_requerido 
                                    ? $this->valor_requerido->getAmount() / 100 
                                    : null,
            'currency'          => $this->currency?->codigo,
            'investor'          => $this->investor 
                                        ? $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name 
                                        : null,
            'document'          => $this->investor?->document,
            'estado_nombre'     => $this->estado,
            'propiedades_count' => $this->properties()->count(),

            // ✅ Configuración en estado 2 (subasta)
            'configuracion_subasta' => $config ? [
                'id'              => $config->id,
                'riesgo'          => $config->riesgo,
                'tem'             => $config->tem, // ya viene procesado en el modelo
                'tea'             => $config->tea,
                'tipo_cronograma' => $config->tipo_cronograma,
                'meses'           => $config->plazo?->duracion_meses,
            ] : null,

            'created_at'        => $this->created_at?->format('Y-m-d H:i:s A'),
            'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s A'),
        ];
    }
}
