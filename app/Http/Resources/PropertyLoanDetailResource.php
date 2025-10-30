<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyLoanDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'config_id' => $this->config_id,
            'investor_id' => $this->investor_id,
            'empresa_tasadora' => $this->empresa_tasadora,
            'motivo_prestamo' => $this->motivo_prestamo,
            'descripcion_financiamiento' => $this->descripcion_financiamiento,
            'solicitud_prestamo_para' => $this->solicitud_prestamo_para,
            'monto_tasacion' => $this->monto_tasacion,
            'porcentaje_prestamo' => $this->porcentaje_prestamo,
            'monto_invertir' => $this->monto_invertir,
            'monto_prestamo' => $this->monto_prestamo,
            'estado_conclusion' => $this->estado_conclusion,

            // Campos de aprobación, si los usas en la interfaz:
            'approval1_status' => $this->approval1_status,
            'approval1_by' => $this->approval1_by,
            'approval1_comment' => $this->approval1_comment,
            'approval1_at' => $this->approval1_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}