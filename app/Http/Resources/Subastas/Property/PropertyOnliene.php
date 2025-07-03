<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyOnliene extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'departamento' => $this->departamento,
            'provincia' => $this->provincia,
            'distrito' => $this->distrito,
            'property_id' => $this->subasta->property_id,
            'descripcion' => $this->descripcion,
            'foto' => $this->getFotoUrl(),
            'monto' => $this->subasta->monto_inicial,
            'tipo' => $this->currency_id,
            'finalizacion' => $this->subasta->tiempo_finalizacion,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'valor_estimado' => $this->valor_estimado ?? 0,
            'Moneda' =>$this->currency->codigo,
            'deadlines_id' => $this->plazo->nombre,
            'Simbolo' => $this->plazo->simbolo,

        ];
    }
    private function getFotoUrl(): string{
        if (empty($this->foto)) {
            return asset('Propiedades/no-image.png');
        }
        $ruta = public_path("Propiedades/{$this->foto}");
        if (!file_exists($ruta)) {
            return asset('Propiedades/no-image.png');
        }
        return asset("Propiedades/{$this->foto}");
    }
}
