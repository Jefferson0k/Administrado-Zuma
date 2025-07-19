<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReservationResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'investor_id' => $this->investor_id,
            'nombre'      => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'dni' =>$this->investor->document,
            'telefono' =>$this->investor->telephone,
            'propiedad' =>$this->property->nombre,
            'tea' =>$this->config->tea,
            'tem' =>$this->config->tem,
            'tem' =>$this->config->tipo_cronograma,
            'riesgo' =>$this->config->riesgo,
            'plazo' =>$this->config->plazo->nombre,
            'property_id' => $this->property_id,
            'config_id' => $this->config_id,
            'amount' => $this->amount,
            'status' => $this->status,
        ];
    }
}
