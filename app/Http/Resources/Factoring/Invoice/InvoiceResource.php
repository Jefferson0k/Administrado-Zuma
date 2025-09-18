<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        // Estados que NO deben mostrar información sensible
        $ocultarEstados = ['rejected', 'observed', 'inactive'];

        // 1️⃣ Situación
        $situacion = null;
        if (!in_array($this->status, $ocultarEstados)) {
            if ($this->estimated_pay_date) {
                $estimatedPayDate = Carbon::parse($this->estimated_pay_date);
                $situacion = Carbon::now()->greaterThan($estimatedPayDate)
                    ? 'vigente mas 8'
                    : 'vigente';
            } else {
                $situacion = 'vigente';
            }
        }

        // 2️⃣ Porcentajes de inversión
        $porcentajeZuma = $porcentajeObjetivoTerceros = $porcentajeInversionTerceros = null;

        if (!in_array($this->status, $ocultarEstados) && $this->amount > 0) {
            // 1️⃣ Porcentaje Zuma (sobre el monto total de la factura)
            $porcentajeZuma = ($this->financed_amount_by_garantia / $this->amount) * 100;
            
            // 2️⃣ Porcentaje objetivo terceros (independiente de Zuma)
            $porcentajeObjetivoTerceros = 100 - $porcentajeZuma;
            
            // 3️⃣ Total de inversiones reales de terceros
            $totalInversionTerceros = $this->relationLoaded('investments')
                ? $this->investments->sum('amount')
                : $this->investments()->sum('amount');

            // 4️⃣ Porcentaje de terceros (basado en el monto DISPONIBLE, no en el total)
            if ($this->financed_amount > 0) {
                $porcentajeInversionTerceros = ($totalInversionTerceros / $this->financed_amount) * 100;
                
                // Limitar al 100% del disponible
                if ($porcentajeInversionTerceros > 100) {
                    $porcentajeInversionTerceros = 100;
                }
            } else {
                $porcentajeInversionTerceros = 0;
            }

            // Redondear
            $porcentajeZuma = round($porcentajeZuma, 2);
            $porcentajeObjetivoTerceros = round($porcentajeObjetivoTerceros, 2);
            $porcentajeInversionTerceros = round($porcentajeInversionTerceros, 2);
        }

        // 3️⃣ Condición oportunidad y fecha cierre
        $condicionOportunidadInversion = $fechaHoraCierreInversion = null;
        if (!in_array($this->status, $ocultarEstados) && $this->due_date) {
            $condicionOportunidadInversion = Carbon::now()->greaterThan(Carbon::parse($this->due_date)) ? 'cerrada' : 'abierta';
            $fechaHoraCierreInversion = Carbon::parse($this->due_date)->format('d-m-Y H:i:s A');
        }

        // 4️⃣ Armar data final
        $data = [
            'id'                         => $this->id,
            'razonSocial'                => $this->company?->name ?? '',
            'codigo'                     => $this->codigo,
            'moneda'                     => $this->currency,
            'montoFactura'               => $this->amount,
            'montoAsumidoZuma'           => $this->financed_amount_by_garantia,
            'montoDisponible'            => $this->financed_amount,
            'tasa'                       => $this->rate,
            'estado'                     => $this->status,
            'situacion'                  => $situacion,
            'invoice_number'             => $this->invoice_number,
            'loan_number'                => $this->loan_number,
            'RUC_client'                 => $this->RUC_client,
            'company_id'                 => $this->company_id,
            'PrimerStado'                => $this->approval1_status,
            'approval1_comment'          => $this->approval1_comment,
            'userprimer'                 => $this->aprovacionuseruno?->dni ?? 'Sin aprobar',
            'userprimerNombre'           => $this->aprovacionuseruno?->name
                                             ? $this->aprovacionuseruno->name.' '.$this->aprovacionuseruno->apellidos
                                             : 'Sin aprobar',
            'SegundaStado'               => $this->approval2_status,
            'tipo'                       => !in_array($this->status, $ocultarEstados) ? $this->type : null,
            'condicionOportunidadInversion'=> $condicionOportunidadInversion,
            'fechaHoraCierreInversion'   => $fechaHoraCierreInversion,
            'porcentajeZuma'             => $porcentajeZuma !== null ? $porcentajeZuma.'%' : null,
            'porcentajeObjetivoTerceros' => $porcentajeObjetivoTerceros !== null ? $porcentajeObjetivoTerceros.'%' : null,
            'porcentajeInversionTerceros'=> $porcentajeInversionTerceros !== null ? $porcentajeInversionTerceros.'%' : null,
            'approval2_comment'          => $this->approval2_comment,
            'userdos'                    => $this->aprovacionuserdos?->dni ?? 'Sin aprobar',
            'userdosNombre'              => $this->aprovacionuserdos?->name
                                             ? $this->aprovacionuserdos->name.' '.$this->aprovacionuserdos->apellidos
                                             : 'Sin aprobar',
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
        ];

        if ($this->relationLoaded('investments')) {
            $data['investments'] = InvestmentListResource::collection($this->investments);
        }

        return $data;
    }
}