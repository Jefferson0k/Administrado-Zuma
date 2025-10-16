<?php

namespace App\Http\Resources\Factoring\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyFinanceResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            "id" => $this->id,
            "company_id" => $this->company_id,
            "nombre" => $this->company->name,
            "facturas_financiadas" => $this->facturas_financiadas,
            "monto_total_financiado" => $this->monto_total_financiado,
            "pagadas" => $this->pagadas,
            "pendientes" => $this->pendientes,
            "plazo_promedio_pago" => $this->plazo_promedio_pago,


            "sales_volume_pen"      => $this->sales_volume_pen,
            "facturas_financiadas_pen" => $this->facturas_financiadas_pen,
            "monto_total_financiado_pen" => $this->monto_total_financiado_pen,
            "pagadas_pen" => $this->pagadas_pen,
            
            "plazo_promedio_pago_pen" => $this->plazo_promedio_pago_pen,
            "sales_volume_usd" => $this->sales_volume_usd,
            "facturas_financiadas_usd" => $this->facturas_financiadas_usd,
            "monto_total_financiado_usd" => $this->monto_total_financiado_usd,
            "plazo_promedio_pago_usd" => $this->plazo_promedio_pago_usd,
        ];
    }
}
