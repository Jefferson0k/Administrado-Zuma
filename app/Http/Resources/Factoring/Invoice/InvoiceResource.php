<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'razonSocial'        => $this->company->name ?? '',
            'codigo'             => $this->codigo,
            'moneda'             => $this->currency,
            'montoFactura'       => $this->amount,
            'montoAsumidoZuma'   => $this->financed_amount_by_garantia,
            'montoDisponible'    => $this->getAvailablePaidAmount()->getAmount(),
            'tasa'               => $this->rate,
            'estado'             => $this->status,
            'fechaPago' => Carbon::parse($this->estimated_pay_date)->format('d-m-Y'),
            'fechaCreacion' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
        ];
    }
}
