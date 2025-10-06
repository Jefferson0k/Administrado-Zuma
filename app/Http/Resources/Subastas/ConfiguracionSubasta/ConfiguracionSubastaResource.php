<?php

namespace App\Http\Resources\Subastas\ConfiguracionSubasta;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionSubastaResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'estado' => $this->estado,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,
            'plazo' => $this->whenLoaded('plazo', fn() => [
                'id' => $this->plazo->id,
                'nombre' => $this->plazo->nombre ?? null,
                'meses' => $this->plazo->meses ?? null,
            ]),
            'subasta' => $this->whenLoaded('subasta', fn() => [
                'id' => $this->subasta->id,
                'fecha_inicio' => $this->subasta->fecha_inicio ?? null,
                'fecha_fin' => $this->subasta->fecha_fin ?? null,
                'estado' => $this->subasta->estado ?? null,
            ]),
            'detalle_inversionista_hipoteca' => $this->whenLoaded('detalleInversionistaHipoteca', fn() => [
                'id' => $this->detalleInversionistaHipoteca->id,
                'monto' => $this->detalleInversionistaHipoteca->monto ?? null,
                'estado' => $this->detalleInversionistaHipoteca->estado ?? null,
            ]),
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
