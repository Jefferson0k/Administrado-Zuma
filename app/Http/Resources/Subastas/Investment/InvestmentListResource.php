<?php

namespace App\Http\Resources\Subastas\Investment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentListResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'return' => $this->return,
            'rate' => $this->rate,
            'codigo' => $this->invoice->codigo ?? 'Sin codigo',
            'currency' => $this->currency,
            'due_date'    => Carbon::parse($this->due_date)->format('d-m-Y'),
            'status' => $this->status,
            'inversionista' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'creacion'    => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
        ];
    }
}