<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyOnliene extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'property_id' => $this->subasta->property_id,
            'distrito' => $this->distrito,
            'descripcion' => $this->descripcion,
            'foto' => $this->getFotoUrl(),
            'monto' => $this->subasta->monto_inicial,
            'finalizacion' => $this->subasta->tiempo_finalizacion,
        ];
    }
    private function getFotoUrl(): string{
        if (empty($this->foto)) {
            return asset('Propiedades/Casas/no-image.png');
        }
        $ruta = public_path("Propiedades/Casas/{$this->foto}");
        if (!file_exists($ruta)) {
            return asset('Propiedades/Casas/no-image.png');
        }
        return asset("Propiedades/Casas/{$this->foto}");
    }
}
