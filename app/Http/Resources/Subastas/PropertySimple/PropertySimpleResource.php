<?php

namespace App\Http\Resources\Subastas\PropertySimple;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertySimpleResource extends JsonResource
{
     public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'departamento' => $this->departamento,
            'provincia' => $this->provincia,
            'distrito' => $this->distrito,
            'direccion' => $this->direccion,
            'valor_estimado' => $this->formatMoney($this->valor_estimado),
            'valor_requerido' => $this->formatMoney($this->valor_requerido),
            'valor_subasta' => $this->formatMoney($this->valor_subasta),
            'estado' => $this->estado,
            'tipo_inmueble' => $this->whenLoaded('tipoInmueble', function () {
                return [
                    'id' => $this->tipoInmueble->id_tipo_inmueble,
                    'nombre' => $this->tipoInmueble->nombre,
                ];
            }),
            'imagenes' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($img) {
                    return [
                        'url' => $img->path ? url("s3/{$img->path}") : asset('Propiedades/no-image.png'),
                        'descripcion' => $img->description ?? '',
                    ];
                });
            }),
        ];
    }

    /**
     * Formatea un objeto Money\Money a array con amount y currency
     *
     * @param mixed $money
     * @return array|null
     */
    private function formatMoney($money)
    {
        if ($money instanceof \Money\Money) {
            return [
                'amount' => $money->getAmount() / 100, // Ajusta si tu Money usa centavos
                'currency' => $money->getCurrency()->getCode(),
            ];
        }

        return $money ? $money : null;
    }
}
