<?php

namespace App\Http\Resources\Factoring\BankAccount;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
class BankAccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'banco' => $this->bank->name,
            'type'        => $this->type,
            'currency'    => $this->currency,
            'cc'          => $this->cc,
            'cci'         => $this->cci,
            'alias'       => $this->alias,
            'inversionista' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'estado'      => $this->getStatusInSpanish(),
            'creacion'    => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'update'      => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }
    private function getStatusInSpanish(){
        return match ($this->status) {
            'valid'        => 'Válido',
            'invalid'      => 'Inválido',
            'pre_approved' => 'Preaprobado',
            default        => 'Desconocido',
        };
    }
}
