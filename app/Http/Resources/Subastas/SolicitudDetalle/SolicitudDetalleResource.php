<?php

namespace App\Http\Resources\Subastas\SolicitudDetalle;

use App\Http\Resources\Subastas\ConfiguracionSubasta\ConfiguracionSubastaResource;
use App\Http\Resources\Subastas\InvestorSimple\InvestorSimpleResource;
use App\Http\Resources\Subastas\PropertyInvestorSimple\PropertyInvestorSimpleResource;
use App\Http\Resources\Subastas\PropertySimple\PropertySimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Money\Money;
use App\Http\Resources\PropertyLoanDetailResource;
use App\Http\Resources\Subastas\SolicitudBid\SolicitudBidResource;

class SolicitudDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'valor_general' => $this->formatMoney($this->valor_general),
            'valor_requerido' => $this->formatMoney($this->valor_requerido),
            'investor' => new InvestorSimpleResource($this->whenLoaded('investor')),
            'currency' => $this->whenLoaded('currency'),
            'estado' => $this->estado,
            'fuente_ingreso' => $this->fuente_ingreso,
            'profesion_ocupacion' => $this->profesion_ocupacion,
            'ingreso_promedio' => $this->ingreso_promedio,
            'config_total' => $this->config_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'properties' => PropertySimpleResource::collection(
                $this->whenLoaded('properties')->sortBy('id') // Ordena si quieres
            ),

            'configuracion_subasta' => new ConfiguracionSubastaResource($this->whenLoaded('configuracionSubasta')),

            'property_investors' => PropertyInvestorSimpleResource::collection(
                $this->whenLoaded('propertyInvestors')->sortBy('id')
            ),
            'subasta' => $this->configuracionSubasta->subasta ? [
                'id' => $this->configuracionSubasta->subasta->id,
                'nombre' => $this->configuracionSubasta->subasta->solicitud?->codigo ?? ('Subasta #' . $this->configuracionSubasta->subasta->id),
                'estado' => $this->configuracionSubasta->subasta->estado,
                'dia_subasta' => $this->configuracionSubasta->subasta->dia_subasta,
                'hora_inicio' => $this->configuracionSubasta->subasta->hora_inicio,
                'hora_fin' => $this->configuracionSubasta->subasta->hora_fin,
                'ganador_nombre' => $this->configuracionSubasta->subasta->ganador
                    ? trim($this->configuracionSubasta->subasta->ganador->name . ' ' . $this->configuracionSubasta->subasta->ganador->first_last_name . ' ' . $this->configuracionSubasta->subasta->ganador->second_last_name)
                    : null,
            ] : null,
            'property_loan_details' => PropertyLoanDetailResource::collection($this->whenLoaded('propertyLoanDetails')),
            'solicitud_bids' => SolicitudBidResource::collection($this->whenLoaded('solicitudBids')),
        ];
    }

    /**
     * Formatea objetos Money\Money a array legible
     */
    private function formatMoney($money)
    {
        if ($money instanceof Money) {
            return [
                'amount' => $money->getAmount() / 100, // Ajusta si tu Money está en centavos
                'currency' => $money->getCurrency()->getCode(),
            ];
        }

        return $money ? $money : null;
    }
}
