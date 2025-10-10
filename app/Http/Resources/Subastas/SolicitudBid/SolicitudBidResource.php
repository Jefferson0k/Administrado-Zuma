<?php

namespace App\Http\Resources\Subastas\SolicitudBid;

use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudBidResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'investor_id' => $this->investor_id,
            'investor_nombre' => $this->investor?->name,
            'estado' => $this->estado,
            'ganador_id' => $this->ganador_id,
            'ganador_nombre' => $this->ganador?->name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
