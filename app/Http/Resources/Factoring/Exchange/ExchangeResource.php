<?php

namespace App\Http\Resources\Factoring\Exchange;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id"                => $this->id,
            "currency"          => $this->currency,
            "exchange_rate_sell"=> $this->exchange_rate_sell,
            "exchange_rate_buy" => $this->exchange_rate_buy,
            "status"            => $this->status,
            "creacion"          => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            "actualizacion"     => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }
}
