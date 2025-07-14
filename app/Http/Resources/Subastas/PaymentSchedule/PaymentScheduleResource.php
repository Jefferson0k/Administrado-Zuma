<?php

namespace App\Http\Resources\Subastas\PaymentSchedule;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentScheduleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'cuota'           => $this->cuota,
            'vencimiento'     => $this->vencimiento,
            'saldo_inicial'   => $this->saldo_inicial,
            'capital'         => $this->capital,
            'intereses'       => $this->intereses,
            'cuota_neta'      => $this->cuota_neta,
            'igv'             => $this->igv,
            'total_cuota'     => $this->total_cuota,
            'saldo_final'     => $this->saldo_final,
            'estado'          => $this->estado,
            'property_investor_id' => $this->property_investor_id,
        ];
    }
}
