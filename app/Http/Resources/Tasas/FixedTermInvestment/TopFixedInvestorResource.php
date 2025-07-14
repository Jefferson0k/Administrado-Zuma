<?php

namespace App\Http\Resources\Tasas\FixedTermInvestment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopFixedInvestorResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'nombre_completo' => trim(
                $this->investor?->name . ' ' .
                $this->investor?->first_last_name . ' ' .
                $this->investor?->second_last_name
            ),
            'monto_total' => number_format($this->total_invested, 2, '.', ''),
        ];
    }
}
