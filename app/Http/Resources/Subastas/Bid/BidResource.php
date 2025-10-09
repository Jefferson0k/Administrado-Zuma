<?php

namespace App\Http\Resources\Subastas\Bid;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BidResource extends JsonResource{
    public function toArray($request){
        $ganadorNombre = null;
        $estadoSubasta = 'En progreso';
        $nombreSubasta = 'Subasta #' . ($this->subasta ? $this->subasta->id : 'N/A');
        if ($this->subasta) {
            if ($this->subasta->property) {
                $nombreSubasta = $this->subasta->property->name ?? $this->subasta->property->address ?? 'Propiedad ' . $this->subasta->property->id;
            }
            
            if ($this->subasta->ganador) {
                $ganadorNombre = $this->subasta->ganador->name . ' ' . 
                               $this->subasta->ganador->first_last_name . ' ' . 
                               $this->subasta->ganador->second_last_name;
                $estadoSubasta = 'Finalizada';
            }
        }
        return [
            'id' => $this->id,
            'monto' => $this->monto,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'updated_at' => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
            'investor' => $this->investor->document,
            'nombre' => $this->investor->name . ' ' . $this->investor->first_last_name . ' ' . $this->investor->second_last_name,
            'subasta_id' => $this->subasta ? $this->subasta->id : null,
            'nombre_subasta' => $nombreSubasta,
            'puesto' => $this->puesto ?? null,
            'es_ganador' => $this->es_ganador ?? false,
            'ganador_nombre' => $ganadorNombre,
            'estado_subasta' => $estadoSubasta,
        ];
    }
}
