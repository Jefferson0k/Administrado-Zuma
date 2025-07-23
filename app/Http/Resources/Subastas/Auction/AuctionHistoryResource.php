<?php

namespace App\Http\Resources\Subastas\Auction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionHistoryResource extends JsonResource{
    public function toArray(Request $request): array{
        $auction = $this->subasta;
        $property = $auction->property;
        $ganador = $auction->ganador;
        
        $resultado = ($ganador && $ganador->id === $this->investors_id) ? 'Ganó' : 'Perdió';
        
        $retorno = $resultado === 'Ganó' ? $this->monto * 1.3 : 0;

        return [
            'id' => $this->id,
            'fecha_participacion' => $this->created_at->format('d/m/Y H:i'),
            'fecha_subasta' => $auction->dia_subasta,
            'resultado' => $resultado,
            'propiedad_nombre' => $property->titulo ?? 'Propiedad N/A',
            'propiedad_ubicacion' => $property->ubicacion ?? '',
            'monto_puja' => number_format($this->monto, 2),
            'monto_inicial_subasta' => number_format($auction->monto_inicial, 2),
            'retorno_estimado' => number_format($retorno, 2),
            'estado_subasta' => $auction->estado,
            'total_pujas' => $auction->pujas()->count(),
            'es_ganador' => $resultado === 'Ganó',
            'auction_id' => $auction->id,
            'property_id' => $property->id ?? null
        ];
    }
}
