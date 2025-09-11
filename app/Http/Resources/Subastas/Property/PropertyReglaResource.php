<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReglaResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombreProperty'=> $this->property->nombre,
            'idProperty'    => $this->property->id,
            'tipo_cronograma'   => $this->tipo_cronograma,
            'deadlines_id'      => $this->deadlines_id,
            'estado_configuracion' => $this->estado,
            'riesgo'            => $this->riesgo,
            'estado'        => $this->property->estado,
            'tea'=> $this->tea !== null ? number_format($this->tea / 100, 3, '.', '') : null,
            'tem'    => $this->tem !== null ? number_format($this->tem / 100, 3, '.', '') : null,
        ];
    }
}

