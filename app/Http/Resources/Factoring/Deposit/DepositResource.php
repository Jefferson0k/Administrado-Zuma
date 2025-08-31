<?php

namespace App\Http\Resources\Factoring\Deposit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            "id" => $this->id,
            'bank_account_id' => $this->bank_account_id,
            'nomBanco' => $this->bankAccount->bank->name ?? 'Sin banco',
            'nro_operation' => $this->nro_operation,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'investor' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'foto' => $this->resource_path,
            'creacion'  => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
        ];
    }
}
