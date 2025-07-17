<?php

namespace App\Http\Resources\Tasas\AmountRange;

use Illuminate\Http\Resources\Json\JsonResource;

class AmountRangeResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'moneda' => $this->moneda,
            'nombre' => $this->corporateEntity->nombre,
            'ruc' => $this->corporateEntity->ruc,
            'estado' => $this->estado === 'activo' ? 'completo' : $this->estado,
        ];
    }
}
