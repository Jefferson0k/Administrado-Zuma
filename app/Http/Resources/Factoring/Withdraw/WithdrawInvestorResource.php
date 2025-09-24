<?php

namespace App\Http\Resources\Factoring\Withdraw;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawInvestorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }
}
