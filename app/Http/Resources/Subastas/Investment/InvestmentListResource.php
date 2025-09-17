<?php

namespace App\Http\Resources\Subastas\Investment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentListResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id'          => $this->id,
            'amount'      => $this->amount,
            'return'      => $this->return,
            'rate'        => $this->rate,
            'codigo'      => $this->invoice->codigo ?? 'Sin codigo',
            'company'     => $this->invoice->company->name ?? 'Sin empresa',
            'currency'    => $this->currency,
            'due_date'    => Carbon::parse($this->due_date)->format('d-m-Y'),
            'status'      => $this->status,
            'investor_id' => $this->investor_id,
            'inversionista' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'correo'      => $this->investor->email,
            'telephone'   => $this->investor->telephone,
            'document'    => $this->investor->document,
            'creacion'    => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),

            // 👇 Aquí las cuentas bancarias
            'bank_accounts' => $this->investor->bankAccounts->map(function ($account) {
                return [
                    'id'       => $account->id,
                    'bank'     => $account->bank->name ?? null,
                    'type'     => $account->type,
                    'currency' => $account->currency,
                    'cc'       => $account->cc,
                    'cci'      => $account->cci,
                    'alias'    => $account->alias,
                    'status'   => $account->status,
                ];
            }),
        ];
    }
}
