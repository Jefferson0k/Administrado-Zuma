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
        ];
    }
}
