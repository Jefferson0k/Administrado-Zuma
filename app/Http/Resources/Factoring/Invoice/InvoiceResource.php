<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;

class InvoiceResource extends JsonResource{
    public function toArray($request){
        $ocultarEstados = ['rejected', 'observed', 'inactive'];
        $situacion = null;
        if ($this->type === 'annulled') {
            $situacion = 'anulado';
        } elseif (!in_array($this->status, $ocultarEstados)) {
            if ($this->statusPago === 'paid') {
                $situacion = 'pagado';
            } elseif ($this->statusPago === 'reprogramed') {
                $situacion = 'reprogramado';
            } else {
                if ($this->estimated_pay_date) {
                    $estimatedPayDate = Carbon::parse($this->estimated_pay_date);
                    $situacion = Carbon::now()->greaterThan($estimatedPayDate->copy()->addDays(8))
                        ? 'vigente más 8'
                        : 'vigente';
                } else {
                    $situacion = 'vigente';
                }
            }
        }
        $porcentajeZuma = $porcentajeMetaTerceros = $porcentajeInversionTerceros = null;
        $limiteAlcanzado = false;
        if ($this->type !== 'annulled' && !in_array($this->status, $ocultarEstados) && $this->amount > 0) {
            $porcentajeZuma = ($this->financed_amount_by_garantia / $this->amount) * 100;
            $metaTercerosMonto = $this->amount - $this->financed_amount_by_garantia;
            $porcentajeMetaTerceros = 100 - $porcentajeZuma;
            $invertidoTerceros = $this->amount - $this->financed_amount_by_garantia - $this->financed_amount;
            if ($invertidoTerceros < 0) $invertidoTerceros = 0;

            if ($metaTercerosMonto > 0) {
                $porcentajeInversionTerceros = ($invertidoTerceros / $metaTercerosMonto) * 100;
                
                if ($porcentajeInversionTerceros >= $porcentajeMetaTerceros) {
                    $porcentajeInversionTerceros = $porcentajeMetaTerceros;
                    $limiteAlcanzado = true;
                }
            } else {
                $porcentajeInversionTerceros = 0;
            }

            $porcentajeZuma = round($porcentajeZuma, 2);
            $porcentajeMetaTerceros = round($porcentajeMetaTerceros, 2);
            $porcentajeInversionTerceros = round($porcentajeInversionTerceros, 2);
        }

        $condicionOportunidadInversion = $fechaHoraCierreInversion = null;
        
        if ($this->type === 'annulled') {
            $condicionOportunidadInversion = 'cerrada';
        } elseif (!in_array($this->status, $ocultarEstados) && $this->due_date) {
            if ($limiteAlcanzado) {
                $condicionOportunidadInversion = 'cerrada';
                $fechaHoraCierreInversion = Carbon::now()->format('d-m-Y H:i:s A');
            } else {
                $condicionOportunidadInversion = Carbon::now()->greaterThan(Carbon::parse($this->due_date))
                    ? 'cerrada'
                    : 'abierta';
                $fechaHoraCierreInversion = Carbon::parse($this->due_date)->format('d-m-Y H:i:s A');
            }
        }

        $data = [
            'id'                         => $this->id,
            'razonSocial'                => $this->company?->name ?? '',
            'ruc'                        => $this->company?->document ?? '',
            'codigo'                     => $this->codigo,
            'moneda'                     => $this->currency,
            'montoFactura'               => $this->amount,
            'montoAsumidoZuma'           => $this->financed_amount_by_garantia,
            'montoDisponible'            => $this->financed_amount,
            'tasa'                       => $this->rate,
            'estado'                     => $this->status,
            'statusPago'                 => $this->statusPago,
            'situacion'                  => $this->condicion_oportunidad,
            'invoice_number'             => $this->invoice_number,
            'loan_number'                => $this->loan_number,
            'RUC_client'                 => $this->RUC_client,
            'company_id'                 => $this->company_id,
            'PrimerStado'                => $this->approval1_status,
            'approval1_comment'          => $this->approval1_comment,
            'userprimer'                 => $this->aprovacionuseruno?->dni ?? null,
            'userprimerNombre'           => $this->aprovacionuseruno?->name
                                             ? $this->aprovacionuseruno->name.' '.$this->aprovacionuseruno->apellidos
                                             : '-',
            'SegundaStado'               => $this->approval2_status,
            'tipo'                       => !in_array($this->status, $ocultarEstados) ? $this->type : null,
            'condicionOportunidadInversion'=> $this->condicion_oportunidad,
            'fechaHoraCierreInversion'   => $fechaHoraCierreInversion,
            'porcentajeZuma'             => $porcentajeZuma !== null ? $porcentajeZuma.'%' : null,
            'porcentajeMetaTerceros'     => $porcentajeMetaTerceros !== null ? $porcentajeMetaTerceros.'%' : null,
            'porcentajeInversionTerceros'=> $porcentajeInversionTerceros !== null ? $porcentajeInversionTerceros.'%' : null,
            'limiteAlcanzado'            => $limiteAlcanzado,
            'approval2_comment'          => $this->approval2_comment,
            'userdos'                    => $this->aprovacionuserdos?->dni ?? '-',
            'userdosNombre'              => $this->aprovacionuserdos?->name
                                             ? $this->aprovacionuserdos->name.' '.$this->aprovacionuserdos->apellidos
                                             : '-',
            'created_by_name'                 => $this->creator?->name ?? '',
            'tiempoUno'                  => $this->approval1_at
                                             ? $this->approval1_at->format('d-m-Y H:i:s A')
                                             : null,
            'tiempoDos'                  => $this->approval2_at
                                             ? $this->approval2_at->format('d-m-Y H:i:s A')
                                             : null,
            'fechaPago'                  => $this->estimated_pay_date
                                             ? Carbon::parse($this->estimated_pay_date)->format('d-m-Y')
                                             : null,
            'fechaCreacion'              => $this->created_at
                                             ? $this->created_at->format('d-m-Y H:i:s A')
                                             : null,
            // 👇 Pagos con evidencias parseadas
            'pagos' => $this->payments->map(function ($payment) {
                return [
                    'id'                      => $payment->id,
                    'invoice_id'              => $payment->invoice_id,
                    'status'                  => $payment->status,
                    'pay_type'                => $payment->pay_type,
                    'amount_to_be_paid'       => $payment->amount_to_be_paid,
                    'amount_to_be_paid_money' => $payment->getAmountToBePaidMoney(),

                    'pay_date'                => $payment->pay_date
                                                    ? Carbon::parse($payment->pay_date)->format('d-m-Y')
                                                    : null,
                    'reprogramation_date'     => $payment->reprogramation_date
                                                    ? Carbon::parse($payment->reprogramation_date)->format('d-m-Y')
                                                    : null,
                    'reprogramation_rate'     => $payment->reprogramation_rate,

                    // 👇 Aquí parseamos JSON a array real
                    'evidencia'               => $payment->evidencia
                                                    ? json_decode($payment->evidencia, true)
                                                    : [],
                    'evidencia_data' => $payment->evidencia_data_parsed,
                    'evidencia_count'         => $payment->evidencia_count,
                    'evidencia_path'          => $payment->evidencia_path,
                    'evidencia_original_name' => $payment->evidencia_original_name,
                    'evidencia_size'          => $payment->evidencia_size,
                    'evidencia_mime_type'     => $payment->evidencia_mime_type,

                    'approval1_status'        => $payment->approval1_status,
                    'approval1_by'            => $payment->approval1_by,
                    'approval1_comment'       => $payment->approval1_comment,
                    'approval1_at'            => $payment->approval1_at
                                                    ? Carbon::parse($payment->approval1_at)->format('d-m-Y H:i:s')
                                                    : null,

                    'approval2_status'        => $payment->approval2_status,
                    'approval2_by'            => $payment->approval2_by,
                    'approval2_comment'       => $payment->approval2_comment,
                    'approval2_at'            => $payment->approval2_at
                                                    ? Carbon::parse($payment->approval2_at)->format('d-m-Y H:i:s')
                                                    : null,

                    'created_at'              => $payment->created_at
                                                    ? $payment->created_at->format('d-m-Y H:i:s')
                                                    : null,
                    'updated_at'              => $payment->updated_at
                                                    ? $payment->updated_at->format('d-m-Y H:i:s')
                                                    : null,
                ];
            }),
        ];

        if ($this->relationLoaded('investments')) {
            $data['investments'] = InvestmentListResource::collection($this->investments);
        }

        return $data;
    }
}