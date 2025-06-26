<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Datos base
        $capital = $this->valor_estimado;
        $tea = $this->tea;
        $tem = $this->tem;
        
        // Calcular TEA y TEM con IGV (18%)
        $teaConIgv = $tea * 1.18;
        $temConIgv = $tem * 1.18;
        
        // Convertir TEM a decimal para cálculos
        $temDecimal = $temConIgv / 100;
        
        // Generar cuadro de amortización
        $cuadroAmortizacion = $this->generateAmortizationTable($capital, $temDecimal);
        
        return [
            'cliente' => 'CLIENTE ID: ' . $this->id,
            'capital' => number_format($capital, 2, '.', ''),
            'moneda' => $this->currency_id == 1 ? 'Soles' : 'Dólares',
            'tea' => number_format($tea, 4, '.', '') . '%',
            'tea_igv' => number_format($teaConIgv, 4, '.', '') . '%',
            'tem' => number_format($tem, 4, '.', '') . '%',
            'tem_igv' => number_format($temConIgv, 4, '.', '') . '%',
            'cuadro_amortizacion' => $cuadroAmortizacion
        ];
    }
    
    private function generateAmortizationTable($capital, $temDecimal)
    {
        $plazos = [12, 18, 24, 36, 48, 60];
        $cuadroAmortizacion = [];
        
        foreach ($plazos as $plazo) {
            // Fórmula de cuota: C * [i * (1+i)^n] / [(1+i)^n - 1]
            $cuotaMensual = $capital * ($temDecimal * pow(1 + $temDecimal, $plazo)) / 
                           (pow(1 + $temDecimal, $plazo) - 1);
            
            $totalPagado = $cuotaMensual * $plazo;
            
            $cuadroAmortizacion[] = [
                'plazo_meses' => $plazo,
                'cuota_mensual' => number_format($cuotaMensual, 2, '.', ''),
                'total_pagado' => number_format($totalPagado, 2, '.', '')
            ];
        }
        
        return $cuadroAmortizacion;
    }
}