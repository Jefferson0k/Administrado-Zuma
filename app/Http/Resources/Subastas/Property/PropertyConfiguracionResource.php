<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyConfiguracionResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,
            'estado' => $this->estado,
            'estado_nombre' => $this->estado === 1 ? 'Inversionista' : ($this->estado === 2 ? 'Cliente' : 'Desconocido'),
            'nombre' =>$this->property->nombre,
            'requerido' =>$this->property->valor_requerido,
            'valor' =>$this->property->valor_estimado,
            'deadlines_id' =>$this->plazo->nombre,
            'estadoProperty' => $this->property->estado,
        ];
    }
}
