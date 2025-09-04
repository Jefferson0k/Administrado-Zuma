<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;

class InvoiceResource extends JsonResource{
    public function toArray($request){
        $data = [
            'id'                => $this->id,
            'razonSocial'       => $this->company->name ?? '',
            'codigo'            => $this->codigo,
            'moneda'            => $this->currency,
            'montoFactura'      => $this->amount,
            'montoAsumidoZuma'  => $this->financed_amount_by_garantia,
            'montoDisponible'   => $this->financed_amount,
            'tasa'              => $this->rate,
            'estado'            => $this->status,
            'invoice_number'    => $this->invoice_number,
            'loan_number'       => $this->loan_number,
            'RUC_client'        => $this->RUC_client,
            'company_id'        => $this->company_id,
            'fechaPago'         => Carbon::parse($this->estimated_pay_date)->format('d-m-Y'),
            'fechaCreacion'     => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
        ];
        if ($this->relationLoaded('investments')) {
            $data['investments'] = InvestmentListResource::collection($this->investments);
        }
        return $data;
    }
}
