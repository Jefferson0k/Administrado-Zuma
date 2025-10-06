<?php

namespace App\Http\Resources\Subastas\PropertyInvestorSimple;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyInvestorSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'inversor_id' => $this->investor_id,
            'monto_invertido' => $this->monto_invertido ?? null,
            'participacion' => $this->participacion ?? null,
            'estado' => $this->estado ?? null,
            'configuracion' => $this->whenLoaded('configuracion', fn() => [
                'id' => $this->configuracion->id,
                'tea' => $this->configuracion->tea,
                'tem' => $this->configuracion->tem,
                'estado' => $this->configuracion->estado,
            ]),
            'payment_schedules' => $this->whenLoaded('paymentSchedules', fn() =>
                $this->paymentSchedules->map(fn($p) => [
                    'cuota' => $p->cuota,
                    'vencimiento' => $p->vencimiento->format('Y-m-d'),
                    'capital' => $p->capital,
                    'intereses' => $p->intereses,
                    'total_cuota' => $p->total_cuota,
                    'estado' => $p->estado,
                ])
            ),
        ];

        return $this->cleanNulls($data);
    }

    private function cleanNulls($array)
    {
        return collect($array)
            ->reject(fn($v) => is_null($v))
            ->toArray();
    }
}
