<?php

namespace App\Http\Resources\Subastas\Property;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'distrito' => $this->distrito,
            'property_id' => $this->subasta->property_id ?? 'null',
            'descripcion' => $this->descripcion,
            'validado' => $this->validado,
            'fecha_inversion' => $this->fecha_inversion
                ? Carbon::parse($this->fecha_inversion)->format('d-m-Y')
                : '00-00-0000',
            'estado' => match($this->estado) {
                'no_subastada' => 'No subastada',
                'en_subasta' => 'En subasta',
                'subastada' => 'Subastada con éxito',
                'desierta' => 'Subasta desierta',
                default => 'Estado desconocido',
            },
            'foto' => $this->getFotoUrl(),
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
