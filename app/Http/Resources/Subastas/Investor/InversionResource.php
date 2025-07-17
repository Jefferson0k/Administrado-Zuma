<?php

namespace App\Http\Resources\Subastas\Investor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class InversionResource extends JsonResource {
    public function toArray(Request $request): array {
        $paymentSchedules = $this->paymentSchedules;

        $pendientes = $paymentSchedules->where('estado', 'pendiente');
        $pagadas = $paymentSchedules->where('estado', 'pagado');
        $vencidas = $paymentSchedules->where('estado', 'vencido');

        $saldoPendienteTotal = $pendientes->sum('total_cuota');

        $ultimaCuotaPagada = $paymentSchedules->where('total_cuota', 0.00)->sortByDesc('vencimiento')->first();
        $fechaUltimaCuota = $ultimaCuotaPagada
            ? Carbon::parse($ultimaCuotaPagada->vencimiento)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')
            : null;

        $proximaCuota = $pendientes->sortBy('vencimiento')->first();
        $proximaFechaPago = $proximaCuota
            ? Carbon::parse($proximaCuota->vencimiento)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')
            : null;

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'property' => [
                'id' => $this->property->id,
                'nombre' => $this->property->nombre,
                'departamento' => $this->property->departamento,
                'distrito' => $this->property->distrito,
                'valor_estimado' => $this->property->valor_requerido,
                'descripcion' => $this->property->descripcion,
                'currency_id' => $this->property->currency->codigo,
                'deadlines_id' => $this->configuracion->plazo->nombre,
                'provincia' => $this->property->provincia,
                'direccion' => $this->property->direccion,
                'tea' => $this->configuracion->tea,
                'tem' => $this->configuracion->tem,
                'riesgo' => $this->configuracion->riesgo,
            ],
            'cuotas_totales' => $paymentSchedules->count(),
            'cuotas_pendientes' => $pendientes->count(),
            'cuotas_pagadas' => $pagadas->count(),
            'cuotas_vencidas' => $vencidas->count(),
            'saldo_pendiente_total' => $saldoPendienteTotal,
            'ultima_fecha_pago' => $fechaUltimaCuota,
            'proxima_fecha_pago' => $proximaFechaPago,
        ];
    }
}