<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id ?? '',
            'nombre'          => $this->nombre ?? 'No disponible',
            'departamento'    => $this->departamento ?? 'No disponible',
            'distrito'        => $this->distrito ?? 'No disponible',
            'provincia'       => $this->provincia ?? 'No disponible',
            'direccion'       => $this->direccion ?? 'No disponible',
            'descripcion'     => $this->descripcion ?? '',
            'valor_estimado'  => $this->valor_estimado?->getAmount() / 100 ?? 0,
            'valor_subasta'   => $this->valor_subasta?->getAmount() / 100 ?? 0,
            'estado'          => $this->estado ?? 'No disponible',
            'foto'            => $this->getImagenes(),
            'id_tipo_inmueble' => $this->id_tipo_inmueble,
            'pertenece'       => $this->pertenece,
            //'tea'             => optional($this->configuracion)->tea ?? 0,
        ];
    }
}
