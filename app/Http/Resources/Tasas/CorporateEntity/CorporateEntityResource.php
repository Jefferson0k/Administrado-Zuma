<?php

namespace App\Http\Resources\Tasas\CorporateEntity;

use Illuminate\Http\Resources\Json\JsonResource;
class CorporateEntityResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'ruc' => $this->ruc,
            'direccion' => $this->direccion,
            'telefono'=> $this->telefono,
            'email'=> $this->email,
            'tipo_entidad'=> $this->tipo_entidad,
            'estado'=> $this->estado,
        ];
    }
}
