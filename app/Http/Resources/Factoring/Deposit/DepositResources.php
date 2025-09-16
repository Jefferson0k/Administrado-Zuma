<?php

namespace App\Http\Resources\Factoring\Deposit;

use Illuminate\Http\Resources\Json\JsonResource;

class DepositResources extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'nro_operation'   => $this->nro_operation,
            'currency'        => $this->currency,
            'amount'          => $this->amount, // ya formateado por el accessor
            'description'     => $this->description,
            'resource_path'   => $this->resource_path,
            'conclusion'      => $this->conclusion,
            
            'movement'        => $this->whenLoaded('movement'),
            'investor'        => $this->whenLoaded('investor'),
            'bank_account'    => $this->whenLoaded('bankAccount'),
            'created_by'      => $this->whenLoaded('createdBy'),
            'updated_by'      => $this->whenLoaded('updatedBy'),

            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),
        ];
    }
}
