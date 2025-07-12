<?php

namespace App\Http\Resources\Subastas\PropertyLoanDetail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyLoanDetailResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'ocupacion_profesion' => $this->ocupacion_profesion,
            'motivo_prestamo' => $this->motivo_prestamo,
            'descripcion_financiamiento' => $this->descripcion_financiamiento,
            'solicitud_prestamo_para' => $this->solicitud_prestamo_para,
            'garantia' => $this->garantia,
            'perfil_riesgo' => $this->perfil_riesgo,

            'Property' => $this->property->nombre,
            'Plazo' => $this->property->plazo->nombre,
            'Esquema' => 'Cuota fija',
            'Monto' => $this->property->valor_requerido,
            'riesgo' => $this->property->riesgo,

            'tea' => $this->property->tea,
            'tem' => $this->property->tem,

            'imagenes' => $this->property->getImagenes(),

            'cronograma' => $this->property->paymentSchedules->map(fn($item) => [
                'cuota' => $item->cuota,
                'vencimiento' => $item->vencimiento,
                'saldo_inicial' => $item->saldo_inicial,
                'capital' => $item->capital,
                'intereses' => $item->intereses,
                'cuota_neta' => $item->cuota_neta,
                'total_cuota' => $item->total_cuota,
                'saldo_final' => $item->saldo_final,
            ]),
            'logo' => url('/imagenes/logo-zuma.svg'),
        ];
    }
}
