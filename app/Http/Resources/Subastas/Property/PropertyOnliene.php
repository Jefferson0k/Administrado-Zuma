<?php
namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;

class PropertyOnliene extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Obtener inversionistas que están pujando en esta subasta
        $inversionistas = [];
        if ($this->subasta) {
            $inversionistas = Bid::where('auction_id', $this->subasta->id)
                ->with('inerson') // Relación con el inversionista
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
            'foto' => $this->getFotoUrl(),
            'monto' => $this->subasta->monto_inicial,
            'tipo' => $this->currency_id,
            'finalizacion' => $this->subasta->tiempo_finalizacion,
            'tea' => $this->tea,
            'tem' => $this->tem,
            'valor_estimado' => $this->valor_estimado ?? 0,
            'Moneda' => $this->currency->codigo,
            'deadlines_id' => $this->plazo->nombre,
            'Simbolo' => $this->plazo->simbolo,
            
            // NUEVA INFORMACIÓN DE INVERSIONISTAS
            'inversionistas_pujando' => $inversionistas,
            'total_inversionistas' => count($inversionistas),
            'monto_actual_mayor' => $inversionistas->isNotEmpty() ? $inversionistas->first()['monto'] : $this->subasta->monto_inicial,

            //Id de Action
            'subasta_id' => $this->subasta->id ?? null,
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