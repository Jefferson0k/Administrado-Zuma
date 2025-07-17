<?php

namespace App\Http\Resources\Tasas\Pagos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoTasaResource extends JsonResource{
    public function toArray(Request $request): array{
        return [
            'id' => $this->id,
            'mes' => $this->mes,
            'monto' => $this->monto,
            'moneda' => $this->moneda,
            'fecha_pago' => $this->created_at?->format('Y-m-d'),

            'inversionista' => [
                'id' => $this->inversionista?->id,
                'nombre_completo' => trim("{$this->inversionista?->name} {$this->inversionista?->first_last_name} {$this->inversionista?->second_last_name}"),
                'documento' => $this->inversionista?->document,
                'email' => $this->inversionista?->email,
                'telefono' => $this->inversionista?->telephone,
                'alias' => $this->inversionista?->alias,
            ],

            'cronograma' => [
                'id' => $this->fixedTermSchedule?->id,
                'mes' => $this->fixedTermSchedule?->month,
                'fecha_programada' => $this->fixedTermSchedule?->payment_date,
                'dias' => $this->fixedTermSchedule?->days,
                'monto_base' => $this->fixedTermSchedule?->base_amount,
                'interes_generado' => $this->fixedTermSchedule?->interest_amount,
                'interes_depositado' => $this->fixedTermSchedule?->interest_to_deposit,
                'retorno_capital' => $this->fixedTermSchedule?->capital_return,
                'saldo_capital' => $this->fixedTermSchedule?->capital_balance,
                'total_a_depositar' => $this->fixedTermSchedule?->total_to_deposit,
                'estado' => $this->fixedTermSchedule?->status,
            ],
        ];
    }
}
