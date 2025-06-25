<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\ScheduleGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GeneracionCronogramaController extends Controller{
    public function generatePaymentSchedule(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'monto_solicitado' => 'required|numeric|min:1',
            'plazo_credito' => 'required|integer|min:1|max:60',
            'fecha_desembolso' => 'required|date'
        ]);

        $property = Property::findOrFail($request->property_id);
        $montoSolicitado = $request->monto_solicitado;
        $plazoCuotas = $request->plazo_credito;
        $fechaDesembolso = Carbon::parse($request->fecha_desembolso);
        
        $tea = $property->tea; 
        $tem = $property->tem;
        
        $cronograma = $this->calculatePaymentSchedule(
            $montoSolicitado, 
            $plazoCuotas, 
            $tem, 
            $fechaDesembolso
        );
        
        return response()->json([
            'cliente' => 1,
            'monto_solicitado' => number_format($montoSolicitado, 2),
            'plazo_credito' => $plazoCuotas,
            'tea' => number_format($tea * 100, 4) . '%',
            'tem' => number_format($tem * 100, 4) . '%',
            'fecha_desembolso' => $fechaDesembolso->format('d/m/Y'),
            'cronograma' => $cronograma
        ]);
    }

    private function calculatePaymentSchedule($capital, $plazo, $tem, $fechaInicio){
        $cronograma = [];
        $saldoInicial = $capital;
        $igvRate = 0.18; // 18% IGV fijo
        
        $cuotaNetaBase = $capital * ($tem * pow(1 + $tem, $plazo)) / (pow(1 + $tem, $plazo) - 1);
        
        for ($i = 1; $i <= $plazo; $i++) {
            $fechaVencimiento = $fechaInicio->copy()->addMonths($i)->day(15);
            $interes = $saldoInicial * $tem;
            $capitalPeriodo = $cuotaNetaBase - $interes;
            if ($i == $plazo) {
                $capitalPeriodo = $saldoInicial;
            }

            $cuotaNeta = $capitalPeriodo + $interes;
            
            $igv = $interes * $igvRate;
            
            // Calcular total de cuota
            $totalCuota = $cuotaNeta + $igv;
            
            // Calcular saldo final
            $saldoFinal = $saldoInicial - $capitalPeriodo;
            
            $cronograma[] = [
                'cuota' => $i,
                'vencimiento' => $fechaVencimiento->format('d/m/Y'),
                'saldo_inicial' => number_format($saldoInicial, 2),
                'capital' => number_format($capitalPeriodo, 2),
                'interes' => number_format($interes, 2),
                'cuota_neta' => number_format($cuotaNeta, 2),
                'igv' => number_format($igv, 2),
                'total_cuota' => number_format($totalCuota, 2),
                'saldo_final' => number_format($saldoFinal, 2)
            ];
            
            $saldoInicial = $saldoFinal;
        }
        
        return $cronograma;
    }
}
