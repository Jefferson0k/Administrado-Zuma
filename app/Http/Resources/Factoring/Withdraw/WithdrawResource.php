<?php

namespace App\Http\Resources\Factoring\Withdraw;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id'              => $this->id,
            'nro_operation'   => $this->nro_operation,
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'deposit_pay_date'=> $this->deposit_pay_date,
            'resource_path'   => $this->resource_path,
            'description'     => $this->description,
            'purpouse'        => $this->purpouse,

            'created_by'      => $this->created_by,
            'updated_by'      => $this->updated_by,

            'movement_id'     => $this->movement_id,
            'investor_id'     => $this->investor_id,
            'bank_account_id' => $this->bank_account_id,

            'invesrionista'   => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'documento'       => $this->investor->document,
            'tipo_banco'           => $this->bank_account->type,
            'cc'              => $this->bank_account->cc,
            'cci'             => $this->bank_account->cci,
            "created_at"          => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'updated_at'      => $this->updated_at,
        ];
    }
}
