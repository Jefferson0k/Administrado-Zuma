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
            'valor_requerido' => $this->valor_requerido?->getAmount() / 100 ?? 0,
            'Moneda'          => $this->currency->codigo ?? 'No disponible',
            'estado'          => $this->estado ?? 'No disponible',
            'estado_nombre'   => match ($this->estado) {
                'en_subasta'   => 'En subasta',
                'activa'       => 'Activa',
                'subastada'    => 'Subastada',
                'programada'   => 'Programada',
                'desactivada'  => 'Desactivada',
                'adquirido'    => 'Adquirido',
                'pendiente'    => 'Pendiente',
                'completo'     => 'Completo',
                'espera'       => 'En espera',
                default        => 'Estado desconocido',
            },
            'foto'            => $this->getImagenes(),
            'tea'             => optional($this->configuracion)->tea ?? 0,
        ];
    }

    private function getImagenes(): array
    {
        $rutaCarpeta = public_path("Propiedades/{$this->id}");
        $imagenes = [];

        if (File::exists($rutaCarpeta)) {
            $archivos = File::files($rutaCarpeta);
            foreach ($archivos as $archivo) {
                $imagenes[] = asset("Propiedades/{$this->id}/" . $archivo->getFilename());
            }
        }

        if (empty($imagenes)) {
            $imagenes[] = asset('Propiedades/no-image.png');
        }

        return $imagenes;
    }
}
