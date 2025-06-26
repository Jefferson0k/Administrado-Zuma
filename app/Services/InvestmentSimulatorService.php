<?php

namespace App\Services;

use App\Models\FixedTermRate;
use App\Models\PaymentFrequency;

class InvestmentSimulatorService{
    public function simular(float $monto, int $plazoEnDias): array{
        $tasas = FixedTermRate::with(['corporateEntity', 'termPlan'])
            ->where('estado', 'activo')
            ->whereHas('corporateEntity', fn($q) => $q->where('estado', 'activo'))
            ->whereHas('termPlan', function ($q) use ($plazoEnDias) {
                $q->where('dias_minimos', '<=', $plazoEnDias)
                ->where('dias_maximos', '>=', $plazoEnDias);
            })
            ->get()
            ->groupBy('corporate_entity_id')
            ->map(function ($group) {
                return $group->sortByDesc('valor')->first(); // Mejor TEA por entidad
            });

        $frecuencias = PaymentFrequency::all();

        $resultados = [];

        foreach ($tasas as $tasa) {
            $entidad = $tasa->corporateEntity;
            $tea = $tasa->valor;

            $opcionesPago = $frecuencias->filter(function ($f) use ($plazoEnDias) {
                return $plazoEnDias % $f->dias === 0;
            })->pluck('nombre')->values()->toArray();

            $resultados[] = [
                'cooperativa' => $entidad->nombre,
                'TEA' => number_format($tea, 2) . '%',
                'plazo' => $tasa->termPlan->nombre,
                'rendimiento_anual' => $this->calcularRendimiento($monto, $tea),
                'opciones_pago' => $opcionesPago,
                'valor' => $tea // campo numérico real para orden
            ];
        }

        // Ordenar de mayor a menor TEA
        usort($resultados, fn($a, $b) => $b['valor'] <=> $a['valor']);

        // Eliminar el campo "valor" antes de retornar
        return array_map(function ($item) {
            unset($item['valor']);
            return $item;
        }, $resultados);
    }

    private function calcularRendimiento(float $monto, float $tea): float{
        $tasaDecimal = $tea / 100;
        return round($monto * $tasaDecimal, 2);
    }
}
