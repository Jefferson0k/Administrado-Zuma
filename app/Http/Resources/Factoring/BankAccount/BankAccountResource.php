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
            'inversionista' => $this->investor->name . ' ' . $this->investor->first_last_name . ' ' . $this->investor->second_last_name,
            'estado'      => $this->getStatusInSpanish(),
            'estado0'       => $this->getStatus0InSpanish(),
            'comment0'      => $this->comment0,
            'comment'       => $this->comment,
            'creacion'    => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'update'      => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),


            // 1ra validación
            'updated0_by'      => $this->updated0_by,
            'updated0_by_name' => optional($this->updated0By)->name,
            'updated0_at'      => $this->updated0_at
                ? Carbon::parse($this->updated0_at)->format('d-m-Y H:i:s A')
                : null,
            // 2da validación
            'updated_by'       => $this->updated_by,
            'updated_by_name'  => optional($this->updatedBy)->name,
            'updated_last_at'  => $this->updated_last_at
                ? Carbon::parse($this->updated_last_at)->format('d-m-Y H:i:s A')
                : null,
        ];
    }
    private function getStatusInSpanish()
    {
        return match ($this->status) {
            'pending'        => 'Pendiente',
            'observed'      => 'Observado',
            'approved' => 'Aprobado',
            'rejected'     => 'Rechazado',
            default        => 'Pendiente',
        };
    }


    private function getStatus0InSpanish()
    {
        return match ($this->status0) {
            'pending'        => 'Pendiente',
            'observed'      => 'Observado',
            'approved' => 'Aprobado',
            'rejected'     => 'Rechazado',
            default        => 'Pendiente',
        };
    }
}
