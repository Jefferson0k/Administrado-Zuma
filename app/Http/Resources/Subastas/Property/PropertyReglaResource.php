<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Money\Money;
use Money\Currency;

class PropertyReglaResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,

            // Supongamos que tea y tem están guardados como enteros (ej: 2500 = 25.00%)
            'tea' => $this->formatPercent($this->tea),
            'tem' => $this->formatPercent($this->tem),

            'tipo_cronograma'   => $this->tipo_cronograma,
            'deadlines_id'      => $this->deadlines_id,
            'estado_configuracion' => $this->estado,
            'riesgo'            => $this->riesgo,

            // Relación con property
            'estado'        => $this->property->estado,
            'nombreProperty'=> $this->property->nombre,
            'idProperty'    => $this->property->id,
        ];
    }

    /**
     * Convierte enteros almacenados como basis points a porcentaje legible
     * Ej: 2500 => "25.00%"
     */
    private function formatPercent($value): string {
        if ($value === null) {
            return null;
        }
        $money = new Money($value, new Currency('PEN')); // Moneda solo como wrapper
        return number_format($money->getAmount() / 100, 2) . '%';
    }
}
