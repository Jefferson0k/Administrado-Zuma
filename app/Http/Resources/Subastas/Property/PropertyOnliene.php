<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PropertyOnliene extends JsonResource{
    public function toArray(Request $request): array{
        $inversionistas = [];
        if ($this->subasta) {
            $inversionistas = Bid::where('auction_id', $this->subasta->id)
                ->with('inerson')
                ->select('investors_id', DB::raw('MAX(monto) as monto_maximo'), DB::raw('MAX(created_at) as fecha_ultima_puja'))
                ->groupBy('investors_id')
                ->orderBy('monto_maximo', 'desc')
                ->get()
                ->map(function ($puja) {
                    return [
                        'nombre' => $puja->inerson->name,
                        'fecha_inversion' => $puja->fecha_ultima_puja,
                        'monto' => $puja->monto_maximo,
                    ];
                });
        }

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'departamento' => $this->departamento,
            'provincia' => $this->provincia,
            'distrito' => $this->distrito,
            'property_id' => $this->subasta->property_id,
            'descripcion' => $this->descripcion,
            'foto' => $this->getImagenes(),
            'monto' => $this->subasta->monto_inicial,
            'tipo' => $this->currency_id,
            'finalizacion' => $this->subasta->tiempo_finalizacion,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'valor_estimado' => $this->valor_estimado ?? 0,
            'Moneda' => $this->currency->codigo,
            'deadlines_id' => $this->plazo->nombre,
            'Simbolo' => $this->plazo->simbolo,
            'inversionistas_pujando' => $inversionistas,
            'total_inversionistas' => count($inversionistas),
            'monto_actual_mayor' => $inversionistas->isNotEmpty() ? $inversionistas->first()['monto'] : $this->subasta->monto_inicial,
            'subasta_id' => $this->subasta->id ?? null,
        ];
    }
    private function getImagenes(): array{
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
