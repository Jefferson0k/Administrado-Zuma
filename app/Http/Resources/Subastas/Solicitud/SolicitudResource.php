<?php

namespace App\Http\Resources\Subastas\Solicitud;

use Carbon\Carbon;
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
            'approval1_status'  => $this->approval1_status,
            'approval1_by' => $this->approvedBy
                ? trim("{$this->approvedBy->name} {$this->approvedBy->apellidos}")
                : null,

            'approval1_at' => $this->approval1_at 
                ? Carbon::parse($this->approval1_at)->format('d-m-Y H:i:s') 
                : null,

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
            'configuracion_subasta' => $config ? [
                'id'              => $config->id,
                'riesgo'          => $config->riesgo,
                'tem'             => $config->tem,
                'tea'             => $config->tea,
                'tipo_cronograma' => $config->tipo_cronograma,
                'meses'           => $config->plazo?->duracion_meses,
            ] : null,

            'created_at'        => $this->created_at?->format('d-m-Y H:i:s A'),
            'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s A'),
        ];
    }
}
