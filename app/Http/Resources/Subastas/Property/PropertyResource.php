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
            'descripcion' => $this->descripcion,
            'valor_estimado' => $this->valor_estimado,
            'tea' => $this->tea,
            'validado' => $this->validado,
            'Moneda' =>$this->currency->codigo,
            'financiado' => 'S/N',
            'fecha_inversion' => $this->fecha_inversion
                ? Carbon::parse($this->fecha_inversion)->format('d-m-Y')
                : '00-00-0000',
            'estado' => $this->estado,
            'estado_nombre' => match($this->estado) {
                'no_subastada' => 'No subastada',
                'programada'   => 'Subasta programada',
                'en_subasta'   => 'En subasta',
                'subastada'    => 'Subastada con éxito',
                'desierta'     => 'Subasta desierta',
                default        => 'Estado desconocido',
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
