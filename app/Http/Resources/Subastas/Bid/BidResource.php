<?php

namespace App\Http\Resources\Subastas\Bid;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BidResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'investor' => [
                'id' => $this->investor->id ?? null,
                'document' => $this->investor->document ?? null,
                'nombre' => trim(($this->investor->name ?? '') . ' ' . ($this->investor->first_last_name ?? '') . ' ' . ($this->investor->second_last_name ?? '')),
            ],
            'subasta' => $this->subasta ? [
                'id' => $this->subasta->id,
                'nombre' => $this->subasta->solicitud?->codigo ?? ('Subasta #' . $this->subasta->id),
                'estado' => $this->subasta->ganador ? 'Finalizada' : 'En progreso',
                'ganador_nombre' => $this->subasta->ganador ? trim($this->subasta->ganador->name . ' ' . $this->subasta->ganador->first_last_name . ' ' . $this->subasta->ganador->second_last_name) : null,
                'puesto' => $this->puesto,
                'es_ganador' => $this->es_ganador ?? false,
            ] : null,
            'solicitud' => $this->solicitudBid && $this->solicitudBid->solicitud ? [
                'id' => $this->solicitudBid->id,
                'codigo' => $this->solicitudBid->solicitud->codigo ?? null,
                'monto_requerido' => $this->solicitudBid->solicitud->valor_requerido?->getAmount() / 100 ?? null,
                'moneda' => $this->solicitudBid->solicitud->currency?->codigo ?? 'PEN',
                'estado' => $this->solicitudBid->solicitud->estado ?? null,
            ] : null,
        ];
    }
}
