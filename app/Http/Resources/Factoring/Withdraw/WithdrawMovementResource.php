<?php

namespace App\Http\Resources\Factoring\Withdraw;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawMovementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'amount'      => $this->amount,
            'currency'    => $this->currency,
            'status'      => $this->status,
            'description' => $this->description,
            'date'        => $this->created_at->toDateString(),
            'related'     => $this->deposit
                ?? $this->withdraw
                ?? $this->investment,
        ];
    }
}
