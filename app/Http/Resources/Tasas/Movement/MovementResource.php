<?php

namespace App\Http\Resources\Tasas\Movement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class MovementResource extends JsonResource{
    public function toArray(Request $request): array{
        return [

            'id'              => $this->id,
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'type'            => $this->type->value,
            'nomInvestor'     => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'codInvestor'     => $this->investor->document,
            'status'          => $this->getTranslatedStatus($this->status?->value),
            'confirm_status'  => $this->getTranslatedStatus($this->confirm_status?->value),
            'description'     => $this->description,
            'investor_id'     => $this->investor_id,
            'nomInvestor'     => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'codInvestor'     => $this->investor->document,
            'deposit' => $this->whenLoaded('deposit', function () {
                return [
                    'id'            => $this->deposit->id,
                    'nro_operation' => $this->deposit->nro_operation,
                    'amount'        => $this->deposit->amount,
                    'currency'      => $this->deposit->currency->value ?? $this->deposit->currency,
                    'payment_source'=> $this->deposit->payment_source,
                    'resource_path' => $this->deposit->resource_path,
                    'created_at'      => $this->formatDateTime($this->deposit->created_at),                 ];
            }),
        ];
    }
    private function getTranslatedStatus(?string $status): ?string{
        return match ($status) {
            'valid'     => 'Válido',
            'invalid'   => 'Inválido',
            'pending'   => 'Pendiente',
            'rejected'  => 'Rechazado',
            'confirmed' => 'Confirmado',
            default     => null,
        };
    }
    private function formatDateTime($date): ?string{
        return $date ? Carbon::parse($date)->format('d-m-y H:i:s') : null;
    }
}
