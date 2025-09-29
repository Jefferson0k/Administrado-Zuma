<?php

namespace App\Http\Resources\Factoring\Invoice;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Subastas\Investment\InvestmentListResource;

class InvoiceResource extends JsonResource{
    public function toArray($request){
        $ocultarEstados = ['rejected', 'observed', 'inactive'];

        // 🔹 Situación (mantener igual)
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

        // 2️⃣ **CÁLCULO DE PORCENTAJES CORREGIDO**
        $porcentajeZuma = $porcentajeMetaTerceros = $porcentajeInversionTerceros = null;
        $limiteAlcanzado = false;

        if ($this->type !== 'annulled' && !in_array($this->status, $ocultarEstados) && $this->amount > 0) {
            // Porcentaje Zuma
            $porcentajeZuma = ($this->financed_amount_by_garantia / $this->amount) * 100;
            
            // Meta de terceros
            $metaTercerosMonto = $this->amount - $this->financed_amount_by_garantia;
            $porcentajeMetaTerceros = 100 - $porcentajeZuma;

            // Calcular lo invertido por terceros correctamente
            $invertidoTerceros = $this->amount - $this->financed_amount_by_garantia - $this->financed_amount;
            if ($invertidoTerceros < 0) $invertidoTerceros = 0;

            // Calcular porcentaje
            if ($metaTercerosMonto > 0) {
                $porcentajeInversionTerceros = ($invertidoTerceros / $metaTercerosMonto) * 100;
                
                // 🔥 **VALIDAR SI SE ALCANZÓ EL LÍMITE**
                if ($porcentajeInversionTerceros >= $porcentajeMetaTerceros) {
                    $porcentajeInversionTerceros = $porcentajeMetaTerceros;
                    $limiteAlcanzado = true; // 🔥 Marcar que se alcanzó el límite
                }
            } else {
                $porcentajeInversionTerceros = 0;
            }

            // Redondear
            $porcentajeZuma = round($porcentajeZuma, 2);
            $porcentajeMetaTerceros = round($porcentajeMetaTerceros, 2);
            $porcentajeInversionTerceros = round($porcentajeInversionTerceros, 2);
        }

        // 3️⃣ **CONDICIÓN OPORTUNIDAD - CORREGIDA CON LÍMITE**
        $condicionOportunidadInversion = $fechaHoraCierreInversion = null;
        
        if ($this->type === 'annulled') {
            $condicionOportunidadInversion = 'cerrada';
        } elseif (!in_array($this->status, $ocultarEstados) && $this->due_date) {
            // 🔥 **NUEVA CONDICIÓN: Si se alcanzó el límite, se cierra automáticamente**
            if ($limiteAlcanzado) {
                $condicionOportunidadInversion = 'cerrada';
                $fechaHoraCierreInversion = Carbon::now()->format('d-m-Y H:i:s A');
            } else {
                // Si no se alcanzó el límite, verificar por fecha
                $condicionOportunidadInversion = Carbon::now()->greaterThan(Carbon::parse($this->due_date))
                    ? 'cerrada'
                    : 'abierta';
                $fechaHoraCierreInversion = Carbon::parse($this->due_date)->format('d-m-Y H:i:s A');
            }
        }

        // 4️⃣ Armar data final (mantener igual)
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
            'situacion'                  => $situacion,
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
            'condicionOportunidadInversion'=> $condicionOportunidadInversion,
            'fechaHoraCierreInversion'   => $fechaHoraCierreInversion,
            'porcentajeZuma'             => $porcentajeZuma !== null ? $porcentajeZuma.'%' : null,
            'porcentajeMetaTerceros'     => $porcentajeMetaTerceros !== null ? $porcentajeMetaTerceros.'%' : null,
            'porcentajeInversionTerceros'=> $porcentajeInversionTerceros !== null ? $porcentajeInversionTerceros.'%' : null,
            'limiteAlcanzado'            => $limiteAlcanzado, // 🔥 Nuevo campo para debug
            'approval2_comment'          => $this->approval2_comment,
            'userdos'                    => $this->aprovacionuserdos?->dni ?? '-',
            'userdosNombre'              => $this->aprovacionuserdos?->name
                                             ? $this->aprovacionuserdos->name.' '.$this->aprovacionuserdos->apellidos
                                             : '-',
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