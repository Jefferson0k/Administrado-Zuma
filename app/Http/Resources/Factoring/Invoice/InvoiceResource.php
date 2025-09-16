<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        $today = Carbon::today();
        $situacion = null;

        if ($this->estimated_pay_date) {
            $fechaVencimiento = Carbon::parse($this->estimated_pay_date);

            if ($this->status === 'cobrada') {
                $situacion = 'cobrada';
            } elseif ($this->status === 'adjudicada') {
                $situacion = 'adjudicada';
            } elseif ($today->lte($fechaVencimiento)) {
                // No está vencido
                $situacion = 'vigente';
            } elseif ($today->gt($fechaVencimiento) && $today->lte($fechaVencimiento->copy()->addDays(8))) {
                // Venció pero dentro de 8 días
                $situacion = 'vigente de los 8 días';
            } else {
                // Venció hace más de 8 días
                $situacion = 'vencida';
            }
        }

        $data = [
            'id'                => $this->id,
            'razonSocial'       => $this->company?->name ?? '',
            'codigo'            => $this->codigo,
            'moneda'            => $this->currency,
            'montoFactura'      => $this->amount,
            'montoAsumidoZuma'  => $this->financed_amount_by_garantia,
            'montoDisponible'   => $this->financed_amount,
            'tasa'              => $this->rate,
            'estado'            => $this->status,
            'situacion'         => $situacion, // 👈 Aquí añadimos la condición
            'invoice_number'    => $this->invoice_number,
            'loan_number'       => $this->loan_number,
            'RUC_client'        => $this->RUC_client,
            'company_id'        => $this->company_id,
            'PrimerStado'       => $this->approval1_status,
            'approval1_comment' => $this->approval1_comment,
            'userprimer'        => $this->aprovacionuseruno?->dni ?? 'Sin aprobar',
            'userprimerNombre'  => $this->aprovacionuseruno?->name
                                    ? $this->aprovacionuseruno->name.' '.$this->aprovacionuseruno->apellidos
                                    : 'Sin aprobar',
            'SegundaStado'      => $this->approval2_status,
            'approval2_comment' => $this->approval2_comment,
            'userdos'           => $this->aprovacionuserdos?->dni ?? 'Sin aprobar',
            'userdosNombre'     => $this->aprovacionuserdos?->name
                                    ? $this->aprovacionuserdos->name.' '.$this->aprovacionuserdos->apellidos
                                    : 'Sin aprobar',
            'tiempoUno'         => $this->approval1_at
                                    ? $this->approval1_at->format('d-m-Y H:i:s A')
                                    : null,
            'tiempoDos'         => $this->approval2_at
                                    ? $this->approval2_at->format('d-m-Y H:i:s A')
                                    : null,
            'fechaPago'         => $this->estimated_pay_date
                                    ? $this->estimated_pay_date->format('d-m-Y')
                                    : null,
            'fechaCreacion'     => $this->created_at
                                    ? $this->created_at->format('d-m-Y H:i:s A')
                                    : null,
        ];

        if ($this->relationLoaded('investments')) {
            $data['investments'] = InvestmentListResource::collection($this->investments);
        }

        return $data;
    }
}
