<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentsInvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'razonSocial'      => $this->company?->name ?? '',
            'ruc'              => $this->company?->document ?? '',
            'codigo'           => $this->codigo,
            'moneda'           => $this->currency,
            'montoFactura'     => $this->amount,
            'montoAsumidoZuma' => $this->financed_amount_by_garantia,
            'montoDisponible'  => $this->financed_amount,
            'tasa'             => $this->rate,
            'estado'           => $this->status,
            'fechaPago'        => $this->estimated_pay_date
                                    ? Carbon::parse($this->estimated_pay_date)->format('d-m-Y')
                                    : null,
            'fechaCreacion'    => $this->created_at
                                    ? $this->created_at->format('d-m-Y H:i:s A')
                                    : null,

            // 👇 Relación con pagos
            'pagos' => $this->payments->map(function ($payment) {
                return [
                    'id'                => $payment->id,
                    'invoice_id'        => $payment->invoice_id,
                    'status'            => $payment->status,
                    'pay_type'          => $payment->pay_type,

                    // Aprobación nivel 1
                    'approval1_status'  => $payment->approval1_status,
                    'approval1_by'      => $payment->approval1User
                                                ? ($payment->approval1User->name . ' ' . $payment->approval1User->apellidos)
                                                : null,
                    'approval1_comment' => $payment->approval1_comment,
                    'approval1_at'      => $payment->approval1_at
                                                ? Carbon::parse($payment->approval1_at)->format('d-m-Y H:i:s')
                                                : null,

                    // Aprobación nivel 2
                    'approval2_status'  => $payment->approval2_status,
                    'approval2_by'      => $payment->approval2User
                                                ? ($payment->approval2User->name . ' ' . $payment->approval2User->apellidos)
                                                : null,
                    'approval2_comment' => $payment->approval2_comment,
                    'approval2_at'      => $payment->approval2_at
                                                ? Carbon::parse($payment->approval2_at)->format('d-m-Y H:i:s')
                                                : null,
                ];
            }),
        ];
    }
}
