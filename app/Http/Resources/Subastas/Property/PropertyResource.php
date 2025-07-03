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
            'departamento' => $this->departamento,
            'distrito' => $this->distrito,
            'provincia' => $this->provincia,
            'direccion' => $this->direccion,
            'descripcion' => $this->descripcion,
            'valor_estimado' => $this->valor_estimado,
            'valor_subasta' => $this->valor_subasta ?? 0,
            'dias' => $this->plazo->duracion_meses??0,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'Moneda' =>$this->currency->codigo,
            'estado_nombre' => match($this->estado) {
                'en_subasta' => 'En subastada',
                'activa'=> 'Activa',
                'subastada' => 'Subastada',
                'programada' => 'Programada',
                'desactivada' => 'Desactivada',
                default        => 'Estado desconocido',
            },
            'foto' => $this->getFotoUrl(),
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
