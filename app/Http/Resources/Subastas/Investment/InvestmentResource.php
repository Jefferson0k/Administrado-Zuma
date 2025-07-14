<?php

namespace App\Http\Resources\Subastas\Investment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'inversor' => $this->customer->alias,
            'monto_invertido' => $this->monto_invertido,
            'fecha_inversion' => $this->formattedFechaInversion(),
            'Ranking' => 'Sigue pujando',
        ];
    }
    protected function formattedFechaInversion(){
        if (!$this->fecha_inversion) {
            return null;
        }

        $carbon = Carbon::parse($this->fecha_inversion);
        return $carbon->locale('es')->isoFormat('ddd HH:mm');
    }
}