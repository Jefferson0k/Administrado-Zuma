<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nro_operation' => $this->nro_operation,
            'amount' => $this->amount,
            'currency' => $this->currency->value,
            'description' => $this->description,
            'type' => $this->type,
            'persona' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'dni' => $this->investor->document,
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
        ];
    }
}
