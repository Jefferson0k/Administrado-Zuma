<?php

namespace App\Http\Resources\Factoring\Investment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Movement;
use Carbon\Carbon;

class InvestmentResource extends JsonResource{
    public function toArray(Request $request): array{
        // Obtener el movimiento de reembolso si existe
        $reemblosoMovement = $this->movement_reembloso 
            ? Movement::with(['deposit.bankAccount.bank'])->find($this->movement_reembloso) 
            : null;

        $deposit = $reemblosoMovement?->deposit;
        $bankAccount = $deposit?->bankAccount;
        $bank = $bankAccount?->bank;

        return [
            'id'                 => $this->id,
            'amount'             => $this->amount,
            'currency'           => $this->currency,
            'due_date'           => $this->due_date,
            'movement_reembloso' => $this->movement_reembloso,

            'investor' => $this->whenLoaded('investor', fn () => [
                'id'       => $this->investor->id,
                'document' => $this->investor->document,
                'name'     => $this->investor->name . ' ' .
                              $this->investor->first_last_name . ' ' .
                              $this->investor->second_last_name,
            ]),

            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id'     => $this->invoice->id,
                'codigo' => $this->invoice->codigo,
                'amount' => $this->invoice->amount,
            ]),

            'movement' => $this->whenLoaded('movement', fn () => [
                'id'       => $this->movement->id,
                'amount'   => $this->movement->amount,
                'currency' => $this->movement->currency,
            ]),

            'deposit_reembloso' => $deposit ? [
                'id'            => $deposit->id,
                'amount'        => $deposit->amount,
                'currency'      => $deposit->currency,
                'resource_path' => $deposit->resource_path,
                'nro_operation' => $deposit->nro_operation,
                'bank_account'  => $bankAccount ? [
                    'id'       => $bankAccount->id,
                    'bank_id'  => $bankAccount->bank_id,
                    'alias'    => $bankAccount->alias,
                    'type'     => $bankAccount->type,
                    'creacion' => $bankAccount->created_at ? Carbon::parse($bankAccount->created_at)->format('d-m-Y H:i:s A') : null,
                    'cc'       => $bankAccount->cc,
                    'cci'      => $bankAccount->cci,
                    'currency' => $bankAccount->currency,
                    'bank_name'=> $bank?->name,
                ] : null,

            ] : null,
        ];
    }
}