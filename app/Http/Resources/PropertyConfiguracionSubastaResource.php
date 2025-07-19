<?php

namespace App\Http\Resources;

use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PropertyConfiguracionSubastaResource extends JsonResource{
    public function toArray(Request $request): array{
        $inversionistas = [];
        if ($this->subasta) {
            $inversionistas = Bid::where('auction_id', $this->subasta->id)
                ->with('investor')
                ->select(
                    'investors_id',
                    DB::raw('MAX(monto) as monto_maximo'),
                    DB::raw('MAX(created_at) as fecha_ultima_puja')
                )
                ->groupBy('investors_id')
                ->orderBy('monto_maximo', 'desc')
                ->get()
                ->map(function ($puja) {
                    return [
                        'nombre' => optional($puja->investor)->name ?? '-',
                        'fecha_inversion' => $puja->fecha_ultima_puja,
                        'monto' => $puja->monto_maximo,
                    ];
                });
        }

        return [
            'id' => $this->id,
            'nombre' => optional($this->property)->nombre,
            'departamento' => optional($this->property)->departamento,
            'provincia' => optional($this->property)->provincia,
            'distrito' => optional($this->property)->distrito,
            'descripcion' => optional($this->property)->descripcion,

            'valor_requerido' => optional($this->property)->valor_requerido,
            'valor_estimado' => optional($this->property)->valor_estimado,
            'valor_subasta' => optional($this->property)->valor_subasta,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,
            'foto' => $this->getImagenes(),

            'subasta' => $this->subasta ? [
                'id' => $this->subasta->id,
                'monto_inicial' => $this->subasta->monto_inicial,
                'dia_subasta' => $this->subasta->dia_subasta,
                'hora_inicio' => $this->subasta->hora_inicio,
                'hora_fin' => $this->subasta->hora_fin,
                'tiempo_finalizacion' => $this->subasta->tiempo_finalizacion,
                'estado' => $this->subasta->estado,
            ] : null,

            'inversionistas_pujando' => $inversionistas,
            'total_inversionistas' => count($inversionistas),
            'monto_actual_mayor' => $inversionistas->isNotEmpty()
                ? $inversionistas->first()['monto']
                : ($this->subasta->monto_inicial ?? 0),

            'property_investor_id' => optional($this->propertyInvestor)->id, // << este es el nuevo campo
        ];
    }
    private function getImagenes(): array{
        $rutaCarpeta = public_path("Propiedades/{$this->property->id}");
        $imagenes = [];
        if (File::exists($rutaCarpeta)) {
            $archivos = File::files($rutaCarpeta);
            foreach ($archivos as $archivo) {
                $imagenes[] = asset("Propiedades/{$this->property->id}/" . $archivo->getFilename());
            }
        }
        if (empty($imagenes)) {
            $imagenes[] = asset('Propiedades/no-image.png');
        }
        return $imagenes;
    }
}
