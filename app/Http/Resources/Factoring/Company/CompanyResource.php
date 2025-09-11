<?php

namespace App\Http\Resources\Factoring\Company;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'risk'             => $this->risk,
            'business_name'    => $this->business_name,
            'incorporation_year' => $this->incorporation_year,
            'document'         => $this->document,
            'link_web_page'    => $this->link_web_page,
            'description'      => $this->description,
            'moneda'           => $this->moneda,
            'sales_PEN'        => $this->sales_PEN,
            'sales_USD'        => $this->sales_USD,
            'sector_id'        => $this->sector_id,
            'subsector_id'     => $this->subsector_id,
            'sectornom'        => $this->sector?->name,
            'subsectornom'     => $this->subsector?->name,
            'nuevonombreempresa' => $this->nuevonombreempresa,
            'creacion'         => $this->created_at ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s A') : null,

            // Datos de la relación finances
            'finances' => $this->whenLoaded('finances', function () {
                return [
                    'facturas_financiadas_pen' => $this->finances->facturas_financiadas_pen,
                    'monto_total_financiado_pen' => $this->finances->monto_total_financiado_pen,
                    'pagadas_pen' => $this->finances->pagadas_pen,
                    'pendientes_pen' => $this->finances->pendientes_pen,
                    'plazo_promedio_pago_pen' => $this->finances->plazo_promedio_pago_pen,

                    #NOtas
                    'facturas_financiadas_usd' => $this->finances->facturas_financiadas_usd,
                    'monto_total_financiado_usd' => $this->finances->monto_total_financiado_usd,
                    'pagadas_usd' => $this->finances->pagadas_usd,
                    'pendientes_usd' => $this->finances->pendientes_usd,
                    'plazo_promedio_pago_usd' => $this->finances->plazo_promedio_pago_usd,
                ];
            }),
        ];
    }
}
