<?php

namespace App\Services;

use App\Models\FixedTermRate;
use App\Models\PaymentFrequency;
use Carbon\Carbon;

class InvestmentSimulatorService
{
    public function simulateByAmount(float $amount): array
    {
        $rates = FixedTermRate::with(['amountRange', 'termPlan', 'corporateEntity', 'rateType'])
            ->where('estado', 'activo')
            ->whereHas('amountRange', function ($query) use ($amount) {
                $query->where('desde', '<=', $amount)
                    ->where(function ($q) use ($amount) {
                        $q->whereNull('hasta')->orWhere('hasta', '>=', $amount);
                    });
            })
            ->get();

        if ($rates->isEmpty()) {
            throw new \Exception('No se encontraron tasas para este monto');
        }

        // Agrupamos solo por cooperativa
        $agrupado = $rates->groupBy('corporate_entity_id');
        $resultado = [];

        foreach ($agrupado as $ratesPorCoop) {
            $cooperativa = $ratesPorCoop->first()->corporateEntity;
            
            // Ahora agrupamos las tasas de esta cooperativa por tipo
            $tiposTasa = $ratesPorCoop->groupBy('rate_type_id');
            
            $tasasPorTipo = [];
            $mejorTasaGeneral = 0;

            foreach ($tiposTasa as $ratesPorTipo) {
                $tipoTasa = $ratesPorTipo->first()->rateType;
                
                $tasas = $ratesPorTipo->map(function ($rate) use ($amount) {
                    $tasaRow = [
                        'id' => $rate->id,
                        'plazo_dias' => $rate->termPlan->dias_maximos,
                        'rate_model' => $rate // Guardamos el modelo completo para el cronograma
                    ];

                    if ($rate->valor_trea !== null && $rate->valor_trem !== null) {
                        $tasaRow['TREA'] = number_format($rate->valor_trea, 2) . '%';
                        $tasaRow['TREM'] = number_format($rate->valor_trem, 2) . '%';

                        $interesTREA = round($amount * ($rate->valor_trea / 100), 2);
                        $interesTREM = round($amount * ($rate->valor_trem / 100), 2);

                        $tasaRow['retorno_trea'] = 'S/ ' . number_format($interesTREA, 2, ',', '.');
                        $tasaRow['retorno_trem'] = 'S/ ' . number_format($interesTREM, 2, ',', '.');

                        $tasaRow['orden_tasa'] = ($rate->valor_trea + $rate->valor_trem) / 2;

                    } elseif ($rate->valor !== null) {
                        $tasaRow['TEA'] = number_format($rate->valor, 2) . '%';
                        $interes = round($amount * ($rate->valor / 100), 2);
                        $tasaRow['retorno'] = 'S/ ' . number_format($interes, 2, ',', '.');
                        $tasaRow['orden_tasa'] = $rate->valor;

                    } elseif ($rate->valor_tem !== null) {
                        $tasaRow['TEM'] = number_format($rate->valor_tem, 2) . '%';
                        $interes = round($amount * ($rate->valor_tem / 100), 2);
                        $tasaRow['retorno'] = 'S/ ' . number_format($interes, 2, ',', '.');
                        $tasaRow['orden_tasa'] = $rate->valor_tem;
                    }

                    return $tasaRow;
                })->sortByDesc(function ($tasa) {
                    return $tasa['orden_tasa'] ?? 0;
                })->values();

                $mejorTasaTipo = $tasas->first()['orden_tasa'] ?? 0;
                if ($mejorTasaTipo > $mejorTasaGeneral) {
                    $mejorTasaGeneral = $mejorTasaTipo;
                }

                $tasasPorTipo[] = [
                    'tipo_tasa' => $tipoTasa->nombre,
                    'tipo_columnas' => $this->detectarColumnas($tasas->first()),
                    'tasas' => $tasas->map(fn($tasa) => collect($tasa)->except(['orden_tasa', 'rate_model']))
                ];
            }

            $resultado[] = [
                'cooperativa' => $cooperativa->nombre,
                'tipos_tasa' => $tasasPorTipo,
                'mejor_tasa' => $mejorTasaGeneral
            ];
        }

        // Ordenamos por mejor tasa
        usort($resultado, fn($a, $b) => $b['mejor_tasa'] <=> $a['mejor_tasa']);

        $ordenes = ['Primera', 'Segunda', 'Tercera', 'Cuarta', 'Quinta'];
        foreach ($resultado as $index => &$coop) {
            $coop['orden'] = $ordenes[$index] ?? ($index + 1) . '°';
            unset($coop['mejor_tasa']);
        }

        return $resultado;
    }

    /**
     * Generar cronograma de pagos para una tasa específica
     */
    public function generatePaymentSchedule(
    int $rateId, 
    float $amount, 
    int $paymentFrequencyId, 
    string $generationDate = null,
    string $startDate = null,
    float $taxRate = 0.05
): array {
    
    $rate = FixedTermRate::with(['termPlan', 'corporateEntity', 'rateType'])
        ->findOrFail($rateId);
                 
    $paymentFrequency = PaymentFrequency::findOrFail($paymentFrequencyId);
             
    // Fecha de generación del cronograma (por defecto hoy)
    $fechaGeneracion = $generationDate ? Carbon::parse($generationDate) : Carbon::now();
    
    // Fecha real de inicio (por defecto misma fecha de generación)
    $fechaInicioReal = $startDate ? Carbon::parse($startDate) : $fechaGeneracion->copy();
    
    // Calcular días transcurridos entre generación e inicio real
    $diasTranscurridos = $fechaGeneracion->diffInDays($fechaInicioReal, false); // false para obtener valor negativo si es en el pasado
    
    $plazoTotal = $rate->termPlan->dias_maximos;
             
    // Determinar la TEA a usar
    $tea = $this->getTEAFromRate($rate);
             
    if (!$tea) {
        throw new \Exception('No se pudo determinar la TEA para esta tasa');
    }
             
    // Generar cronograma con fechas ajustadas
    $cronograma = $this->buildScheduleWithFixedPeriods(
        $amount, 
        $tea / 100,
        $plazoTotal, 
        $paymentFrequency->dias, 
        $fechaGeneracion,
        $fechaInicioReal,
        $diasTranscurridos,
        $taxRate
    );
             
    // Calcular resumen
    $resumen = $this->calculateSummary($cronograma, $amount, $tea / 100);
             
    return [
        'cooperativa' => $rate->corporateEntity->nombre,
        'tipo_tasa' => $rate->rateType->nombre,
        'tea_aplicada' => $tea,
        'monto_invertido' => $amount,
        'plazo_dias' => $plazoTotal,
        'frecuencia_pago' => $paymentFrequency->nombre,
        'impuesto_tasa' => $taxRate * 100,
        'fecha_generacion' => $fechaGeneracion->format('Y-m-d'),
        'fecha_inicio_real' => $fechaInicioReal->format('Y-m-d'),
        'dias_transcurridos' => $diasTranscurridos,
        'resumen' => $resumen,
        'cronograma' => $cronograma
    ];
}
private function buildScheduleWithFixedPeriods(
    float $capital,
    float $tea,
    int $plazoTotal,
    int $diasPeriodoFijo,
    Carbon $fechaGeneracion,
    Carbon $fechaInicioReal,
    int $diasTranscurridos,
    float $impuestoTasa
): array {
    $cronograma = [];
    $saldoCapital = $capital;
    $diasAcumulados = 0;
    $numeroPago = 0;

    // Fila inicial: fecha de generación
    $cronograma[] = [
        'numero_pago' => 0,
        'fecha_cronograma' => $fechaGeneracion->format('d/m/Y'),
        'fecha_inicio_real' => $fechaInicioReal->format('d/m/Y'),
        'fecha_pago' => null,
        'dias_periodo' => null,
        'dias_transcurridos' => $diasTranscurridos,
        'monto_base' => $capital,
        'interes_bruto' => 0,
        'impuesto_2da' => 0,
        'interes_neto' => 0,
        'devolucion_capital' => 0,
        'saldo_capital' => $capital,
        'total_a_depositar' => 0,
        'es_pago' => false,
        'es_final' => false,
        'nota' => 'Fecha de generación del cronograma'
    ];

    // Si hay días transcurridos antes de la firma, generar primer interés
    if ($diasTranscurridos > 0) {
        $monto_base = $saldoCapital;

        $tasaPeriodo = pow(1 + $tea, $diasTranscurridos / 360) - 1;
        $interesBruto = $monto_base * $tasaPeriodo;
        $impuesto2da = $interesBruto * $impuestoTasa;
        $interesNeto = $interesBruto - $impuesto2da;

        $saldoCapital += $interesNeto;
        $diasAcumulados = $diasTranscurridos;
        $numeroPago++;

        $cronograma[] = [
            'numero_pago' => $numeroPago,
            'fecha_cronograma' => $fechaInicioReal->format('d/m/Y'),
            'fecha_inicio_real' => $fechaInicioReal->format('d/m/Y'),
            'fecha_pago' => $this->getNextBusinessDay($fechaInicioReal)->format('d/m/Y'),
            'dia_semana_pago' => $this->getDayName($fechaInicioReal->dayOfWeek),
            'dias_periodo' => $diasTranscurridos,
            'periodo_plan' => null,
            'dias_acumulados' => $diasAcumulados,
            'dias_restantes' => $plazoTotal - $diasAcumulados,
            'monto_base' => $monto_base,
            'interes_bruto' => $interesBruto,
            'impuesto_2da' => $impuesto2da,
            'interes_neto' => $interesNeto,
            'devolucion_capital' => 0,
            'saldo_capital' => $saldoCapital,
            'total_a_depositar' => $interesNeto,
            'es_pago' => true,
            'es_final' => false,
            'nota' => "Primer período desde generación hasta firma ({$diasTranscurridos} días)"
        ];
    }

    // Iniciar desde la fecha de firma
    $fechaProximoPago = $fechaInicioReal->copy();

    while ($diasAcumulados < $plazoTotal) {
        $numeroPago++;

        $diasEstePeriodo = $diasPeriodoFijo;
        if ($diasAcumulados + $diasEstePeriodo > $plazoTotal) {
            $diasEstePeriodo = $plazoTotal - $diasAcumulados;
        }

        $diasAcumulados += $diasEstePeriodo;
        $monto_base = $saldoCapital;

        $fechaCronograma = $fechaProximoPago->copy();
        $fechaPago = $this->getNextBusinessDay($fechaCronograma);
        $tasaPeriodo = pow(1 + $tea, $diasEstePeriodo / 360) - 1;

        $interesBruto = $monto_base * $tasaPeriodo;
        $impuesto2da = $interesBruto * $impuestoTasa;
        $interesNeto = $interesBruto - $impuesto2da;

        $esUltimoPago = $diasAcumulados >= $plazoTotal;
        $devolucionCapital = $esUltimoPago ? $monto_base : 0;
        $totalDepositar = $interesNeto + $devolucionCapital;

        $cronograma[] = [
            'numero_pago' => $numeroPago,
            'fecha_cronograma' => $fechaCronograma->format('d/m/Y'),
            'fecha_inicio_real' => $fechaInicioReal->format('d/m/Y'),
            'fecha_pago' => $fechaPago->format('d/m/Y'),
            'dia_semana_pago' => $this->getDayName($fechaPago->dayOfWeek),
            'dias_periodo' => $diasEstePeriodo,
            'periodo_plan' => $diasPeriodoFijo,
            'dias_acumulados' => $diasAcumulados,
            'dias_restantes' => max(0, $plazoTotal - $diasAcumulados),
            'monto_base' => $monto_base,
            'interes_bruto' => $interesBruto,
            'impuesto_2da' => $impuesto2da,
            'interes_neto' => $interesNeto,
            'devolucion_capital' => $devolucionCapital,
            'saldo_capital' => $esUltimoPago ? 0 : $monto_base + $interesNeto,
            'total_a_depositar' => $totalDepositar,
            'es_pago' => true,
            'es_final' => $esUltimoPago,
            'nota' => $esUltimoPago
                ? "Pago final - Término del plan ({$plazoTotal} días)"
                : "Pago {$numeroPago} - Período de {$diasEstePeriodo} días"
        ];

        if (!$esUltimoPago) {
            $saldoCapital = $monto_base + $interesNeto;
            $fechaProximoPago->addDays($diasPeriodoFijo);
        }   
    }

    return $cronograma;
}


private function getPreviousBusinessDay(Carbon $date): Carbon
{
    $previousDay = $date->copy();
    
    // Si es fin de semana, retroceder al viernes anterior
    while (in_array($previousDay->dayOfWeek, [0, 6])) { // 0=Domingo, 6=Sábado
        $previousDay->subDay();
    }
    
    return $previousDay;
}
private function getNextBusinessDay(Carbon $date): Carbon
{
    $nextDay = $date->copy();
    
    // Si es fin de semana, avanzar al siguiente lunes
    while (in_array($nextDay->dayOfWeek, [0, 6])) { // 0=Domingo, 6=Sábado
        $nextDay->addDay();
    }
    
    return $nextDay;
}

private function calculateBusinessDays(Carbon $startDate, Carbon $endDate): int
{
    $businessDays = 0;
    $currentDate = $startDate->copy();
    
    while ($currentDate->lte($endDate)) {
        // Si no es sábado (6) ni domingo (0)
        if (!in_array($currentDate->dayOfWeek, [0, 6])) {
            $businessDays++;
        }
        $currentDate->addDay();
    }
    
    return $businessDays;
}

// Método para agregar días hábiles
private function addBusinessDays(Carbon $date, int $days): Carbon
{
    $result = $date->copy();
    $addedDays = 0;
    
    while ($addedDays < $days) {
        $result->addDay();
        // Si no es fin de semana, contar el día
        if (!in_array($result->dayOfWeek, [0, 6])) {
            $addedDays++;
        }
    }
    
    return $result;
}

// Método helper para obtener nombre del día
private function getDayName(int $dayOfWeek): string
{
    $days = [
        0 => 'Domingo',
        1 => 'Lunes', 
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado'
    ];
    
    return $days[$dayOfWeek] ?? 'Desconocido';
}
    /**
     * Extraer TEA del modelo de tasa
     */
    private function getTEAFromRate(FixedTermRate $rate): ?float
    {
        if ($rate->valor !== null) {
            return $rate->valor; // TEA directa
        }
        
        if ($rate->valor_trea !== null) {
            return $rate->valor_trea; // TREA
        }
        
        if ($rate->valor_tem !== null) {
            // Convertir TEM a TEA: TEA = (1 + TEM)^12 - 1
            return (pow(1 + ($rate->valor_tem / 100), 12) - 1) * 100;
        }
        
        if ($rate->valor_trem !== null) {
            // Si solo hay TREM, usamos esa (aunque no es lo ideal)
            return $rate->valor_trem;
        }
        
        return null;
    }

    /**
     * Construir el cronograma de pagos
     */
    private function buildSchedule(
        float $capital, 
        float $tea, 
        int $plazoTotal, 
        int $frecuenciaDias, 
        Carbon $fechaInicio, 
        float $impuestoTasa
    ): array {
        
        $cronograma = [];
        $saldoCapital = $capital;
        $diasAcumulados = 0;
        $mes = 0;
        
        // Fila inicial
        $cronograma[] = [
            'mes' => $mes,
            'fecha_cronograma' => $fechaInicio->format('d/m/Y'),
            'fecha_pago' => null,
            'dias_periodo' => null,
            'monto_base' => $capital,
            'interes_bruto' => 0,
            'impuesto_2da' => 0,
            'interes_neto' => 0,
            'devolucion_capital' => 0,
            'saldo_capital' => $capital,
            'total_a_depositar' => 0,
            'es_pago' => false,
            'es_final' => false
        ];
        
        while ($diasAcumulados < $plazoTotal) {
            $mes++;
            $diasPeriodo = min($frecuenciaDias, $plazoTotal - $diasAcumulados);
            $diasAcumulados += $diasPeriodo;
            
            $fechaCronograma = $fechaInicio->copy()->addDays($diasAcumulados);
            $fechaPago = $fechaCronograma->copy();
            
            $esPeriodoPago = ($diasAcumulados % $frecuenciaDias === 0) || ($diasAcumulados >= $plazoTotal);
            $esUltimoPago = $diasAcumulados >= $plazoTotal;
            
            if ($esPeriodoPago) {
                // Calcular tasa para el período
                $tasaPeriodo = pow(1 + $tea, $diasPeriodo / 360) - 1;
                $interesBruto = $saldoCapital * $tasaPeriodo;
                $impuesto2da = $interesBruto * $impuestoTasa;
                $interesNeto = $interesBruto - $impuesto2da;
                
                $devolucionCapital = $esUltimoPago ? $capital : 0;
                $totalDepositar = $interesNeto + $devolucionCapital;
                
                $cronograma[] = [
                    'mes' => $mes,
                    'fecha_cronograma' => $fechaCronograma->format('d/m/Y'),
                    'fecha_pago' => $fechaPago->format('d/m/Y'),
                    'dias_periodo' => $diasPeriodo,
                    'monto_base' => $saldoCapital,
                    'interes_bruto' => $interesBruto,
                    'impuesto_2da' => $impuesto2da,
                    'interes_neto' => $interesNeto,
                    'devolucion_capital' => $devolucionCapital,
                    'saldo_capital' => $esUltimoPago ? 0 : $capital,
                    'total_a_depositar' => $totalDepositar,
                    'es_pago' => true,
                    'es_final' => $esUltimoPago
                ];
                
                if (!$esUltimoPago) {
                    // Reiniciar el saldo para el siguiente período
                    $saldoCapital = $capital;
                }
            } else {
                // Capitalización sin pago
                $tasaPeriodo = pow(1 + $tea, $diasPeriodo / 360) - 1;
                $saldoCapital = $saldoCapital * (1 + $tasaPeriodo);
                
                $cronograma[] = [
                    'mes' => $mes,
                    'fecha_cronograma' => $fechaCronograma->format('d/m/Y'),
                    'fecha_pago' => $fechaPago->format('d/m/Y'),
                    'dias_periodo' => null,
                    'monto_base' => $saldoCapital,
                    'interes_bruto' => 0,
                    'impuesto_2da' => 0,
                    'interes_neto' => 0,
                    'devolucion_capital' => 0,
                    'saldo_capital' => $saldoCapital,
                    'total_a_depositar' => 0,
                    'es_pago' => false,
                    'es_final' => false
                ];
            }
        }
        
        return $cronograma;
    }

    /**
     * Calcular resumen financiero
     */
    private function calculateSummary(array $cronograma, float $capitalInicial, float $tea): array
    {
        $totalInteresBruto = 0;
        $totalImpuesto = 0;
        $totalInteresNeto = 0;
        
        foreach ($cronograma as $fila) {
            if ($fila['es_pago']) {
                $totalInteresBruto += $fila['interes_bruto'];
                $totalImpuesto += $fila['impuesto_2da'];
                $totalInteresNeto += $fila['interes_neto'];
            }
        }
        
        // Calcular tasas derivadas
        $tem = pow(1 + $tea, 1/12) - 1;
        $tet = pow(1 + $tea, 1/4) - 1;
        $rentabilidadNeta = ($totalInteresNeto / $capitalInicial) * 100;
        
        return [
            'tem' => $tem * 100,
            'tet' => $tet * 100,
            'total_interes_bruto' => $totalInteresBruto,
            'total_impuesto' => $totalImpuesto,
            'total_interes_neto' => $totalInteresNeto,
            'total_a_recibir' => $capitalInicial + $totalInteresNeto,
            'rentabilidad_neta' => $rentabilidadNeta
        ];
    }

    /**
     * Obtener frecuencias de pago disponibles
     */
    public function getPaymentFrequencies(): array
    {
        return PaymentFrequency::orderBy('dias')->get(['id', 'nombre', 'dias'])->toArray();
    }

    /**
     * Simulación completa con múltiples frecuencias
     */
    public function completeSimulation(int $rateId, float $amount): array
    {
        $frequencies = PaymentFrequency::all();
        $simulations = [];
        
        foreach ($frequencies as $frequency) {
            try {
                $simulation = $this->generatePaymentSchedule(
                    $rateId, 
                    $amount, 
                    $frequency->id
                );
                
                $simulations[] = [
                    'frecuencia' => $frequency->nombre,
                    'frecuencia_dias' => $frequency->dias,
                    'rentabilidad_neta' => $simulation['resumen']['rentabilidad_neta'],
                    'total_interes_neto' => $simulation['resumen']['total_interes_neto'],
                    'total_a_recibir' => $simulation['resumen']['total_a_recibir'],
                    'simulation_data' => $simulation
                ];
            } catch (\Exception $e) {
                // Si hay error con alguna frecuencia, continuamos con las demás
                continue;
            }
        }
        
        // Ordenar por mejor rentabilidad
        usort($simulations, fn($a, $b) => $b['rentabilidad_neta'] <=> $a['rentabilidad_neta']);
        
        return $simulations;
    }

    private function detectarColumnas($tasa)
    {
        if (isset($tasa['TREA']) && isset($tasa['TREM'])) {
            return ['TREA', 'TREM', 'plazo_dias', 'retorno_trea', 'retorno_trem'];
        }

        if (isset($tasa['TEA'])) {
            return ['TEA', 'plazo_dias', 'retorno'];
        }

        if (isset($tasa['TEM'])) {
            return ['TEM', 'plazo_dias', 'retorno'];
        }

        return ['plazo_dias'];
    }
}
