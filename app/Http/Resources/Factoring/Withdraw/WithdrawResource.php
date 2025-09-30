<?php

namespace App\Http\Resources\Factoring\Withdraw;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nro_operation'    => $this->nro_operation,
            'amount'           => $this->amount,
            'currency'         => $this->currency,
            'deposit_pay_date' => $this->deposit_pay_date,

            // 🔥 Aquí ya usa resource_path de la BD
            'resource_path'    => $this->resource_path,

            'description'      => $this->description,
            'purpouse'         => $this->purpouse,

            'created_by'       => $this->created_by,
            'updated_by'       => $this->updated_by,

            'movement_id'      => $this->movement_id,
            'investor_id'      => $this->investor_id,
            'bank_account_id'  => $this->bank_account_id,

            'invesrionista'    => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'documento'        => $this->investor->document,
            'tipo_banco'       => $this->bank_account->type,
            'cc'               => $this->bank_account->cc,
            'cci'              => $this->bank_account->cci,

            'status'            => $this->status,

            'approval1_status'  => $this->approval1_status,
            'approval1_by'      => $this->approvalUserOne ? $this->approvalUserOne->name . ' ' . $this->approvalUserOne->apellidos : null,
            'approval1_comment' => $this->approval1_comment,
            'approval1_at'      => $this->approval1_at ? Carbon::parse($this->approval1_at)->format('d-m-Y H:i:s A') : null,

            'approval2_status'  => $this->approval2_status,
            'approval2_by'      => $this->approvalUserTwo ? $this->approvalUserTwo->name . ' ' . $this->approvalUserTwo->apellidos : null,
            'approval2_comment' => $this->approval2_comment,
            'approval2_at'      => $this->approval2_at ? Carbon::parse($this->approval2_at)->format('d-m-Y H:i:s A') : null,



            'payment_comment'  => $this->payment_comment,
            'created_at'        => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'updated_at'        => $this->updated_at,
        ];
    }
}
