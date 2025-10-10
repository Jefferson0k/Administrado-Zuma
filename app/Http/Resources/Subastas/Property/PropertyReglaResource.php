<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReglaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'nombreSolicitud'       => $this->solicitud->codigo,

            'valor_general'         => $this->solicitud->valor_general
                                        ? $this->solicitud->valor_general->getAmount() / 100
                                        : null,
            'valor_requerido'       => $this->solicitud->valor_requerido
                                        ? $this->solicitud->valor_requerido->getAmount() / 100
                                        : null,

            'currency_id'           => $this->solicitud->currency_id,
            'currency'              => $this->solicitud->currency->nombre,

            'solicitud_id'          => $this->solicitud_id,
            'tipo_cronograma'       => $this->tipo_cronograma,
            'deadlines_id'          => $this->deadlines_id,
            'estado_configuracion'  => $this->estado,
            'riesgo'                => $this->riesgo,

            'tea'                   => $this->tea !== null 
                                        ? number_format($this->tea / 100, 3, '.', '') 
                                        : null,
            'tem'                   => $this->tem !== null 
                                        ? number_format($this->tem / 100, 3, '.', '') 
                                        : null,
        ];
    }
}
