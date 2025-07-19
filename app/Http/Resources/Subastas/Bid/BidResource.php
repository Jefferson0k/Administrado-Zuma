<?php

namespace App\Http\Resources\Subastas\Bid;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BidResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'monto' => $this->monto,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y H:i'),
            'updated_at' => Carbon::parse($this->updated_at)->format('d-m-Y H:i'),
            'investor' => $this->investor->document,
            'nombre' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
        ];
    }
}
