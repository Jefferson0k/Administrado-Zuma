<?php

namespace App\Http\Resources\Subastas\PagosHipotecas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HipotecasInvestorResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'nombre' => $this->property->nombre,
            'investor_id' => $this->investor_id,
            'nombreinvestor' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'document' => $this->investor->document,
            'config_id' => $this->config_id,
            'amount' =>$this->amount,
            'status' =>$this->status
        ];
    }
}
