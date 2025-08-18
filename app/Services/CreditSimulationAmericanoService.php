<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Deadlines;
use Carbon\Carbon;

class CreditSimulationAmericanoService
{
    /**
     * Función para redondear usando BC Math
     */
    private function bcround(string $number, int $precision = 0): string
    {
        if ($precision < 0) {
            $precision = 0;
        }
        
        $factor = bcpow('10', (string)$precision, $precision + 2);
        $rounded = bcdiv(bcadd(bcmul($number, $factor, $precision + 2), '0.5', $precision + 2), $factor, $precision);
        
        return $rounded;
    }
    public function generate(Property $property, Deadlines $deadline, int $page = 1, int $perPage = 10): array
    {
        bcscale(6);
        
        // FIX: Usar getAmount() para obtener el valor en centavos y convertir a string
        $valorRequeridoMoney = $property->valor_requerido;
        $valorRequeridoCentavos = $valorRequeridoMoney->getAmount(); // Ya está en centavos
        $capital = bcdiv((string) $valorRequeridoCentavos, '100', 6); // Convertir centavos a unidades
        
        $plazoMeses = $deadline->duracion_meses;
        $moneda = $property->currency_id == 1 ? 'Soles' : 'Dólares';
        $simbolo = $property->currency_id == 1 ? 'PEN' : 'USD';
        $tem_sin_igv = bcdiv((string) $property->tem, '100', 6);
        $fechaDesembolso = Carbon::now()->format('d/m/Y');
        $fechaInicio = Carbon::now()->addMonth()->day(15);
        
        $pagos = [];
        
        for ($cuota = 1; $cuota <= $plazoMeses; $cuota++) {
            $interesSinIGV = bcmul($capital, $tem_sin_igv, 6);
            $igv = '0.00';
            $capitalPago = ($cuota === $plazoMeses) ? $capital : '0.00';
            $cuotaNeta = bcadd($interesSinIGV, $capitalPago, 6);
            $cuotaTotal = $cuotaNeta;
            $saldoInicial = $capital;
            $saldoFinal = ($cuota === $plazoMeses) ? '0.00' : $capital;
            
            $fechaVcmto = $fechaInicio->copy()->addMonths($cuota - 1)->format('d/m/Y');
            
            $pagos[] = [
                'cuota' => $cuota,
                'vcmto' => $fechaVcmto,
                'saldo_inicial' => $this->bcround($saldoInicial, 2),
                'capital' => $this->bcround($capitalPago, 2),
                'interes' => $this->bcround($interesSinIGV, 2),
                'cuota_neta' => $this->bcround($cuotaNeta, 2),
                'igv' => $this->bcround($igv, 2),
                'total_cuota' => $this->bcround($cuotaTotal, 2),
                'saldo_final' => $this->bcround($saldoFinal, 2),
            ];
        }
        
        $total = count($pagos);
        $offset = ($page - 1) * $perPage;
        $paginatedPagos = array_slice($pagos, $offset, $perPage);
        
        return [
            'cliente' => 'CLIENTE SIMULACION',
            'monto_solicitado' => $this->bcround($capital, 2),
            'plazo_credito' => $plazoMeses,
            'tasa_efectiva_mensual' => number_format($property->tem, 4, '.', '') . '%',
            'tasa_efectiva_anual' => number_format($property->tea, 4, '.', '') . '%',
            'fecha_desembolso' => $fechaDesembolso . ' referencial',
            'cronograma_final' => [
                'plazo_total' => $plazoMeses,
                'moneda' => $moneda,
                'capital_otorgado' => $simbolo . ' ' . number_format((float)$capital, 2, '.', ','),
                'tea_compensatoria' => number_format($property->tea, 2, '.', '') . ' %',
                'tem_compensatoria' => number_format($property->tem, 2, '.', '') . ' %',
                'pagos' => $paginatedPagos,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ]
        ];
    }
}

// También actualizar el servicio francés para consistencia
class CreditSimulationService
{
    /**
     * Función para redondear usando BC Math
     */
    private function bcround(string $number, int $precision = 0): string
    {
        if ($precision < 0) {
            $precision = 0;
        }
        
        $factor = bcpow('10', (string)$precision, $precision + 2);
        $rounded = bcdiv(bcadd(bcmul($number, $factor, $precision + 2), '0.5', $precision + 2), $factor, $precision);
        
        return $rounded;
    }
    public function generate(Property $property, Deadlines $deadline, int $page = 1, int $perPage = 10): array
    {
        bcscale(6);
        
        // FIX: Usar getAmount() para obtener el valor en centavos y convertir a string
        $valorRequeridoMoney = $property->valor_requerido;
        $valorRequeridoCentavos = $valorRequeridoMoney->getAmount(); // Ya está en centavos
        $capital = bcdiv((string) $valorRequeridoCentavos, '100', 6); // Convertir centavos a unidades
        
        $plazoMeses = $deadline->duracion_meses;
        $moneda = $property->currency_id == 1 ? 'Soles' : 'Dólares';
        $simbolo = $property->currency_id == 1 ? 'PEN' : 'USD';
        $tem_sin_igv = bcdiv((string) $property->tem, '100', 6);
        $fechaDesembolso = Carbon::now()->format('d/m/Y');
        $fechaInicio = Carbon::now()->addMonth()->day(15);
        
        $saldoInicial = $capital;
        $pagos = [];
        
        for ($cuota = 1; $cuota <= $plazoMeses; $cuota++) {
            $interesSinIGV = bcmul($saldoInicial, $tem_sin_igv, 6);
            $igv = '0.00';
            $capitalPago = $this->calcularCapitalPagoBC($capital, $tem_sin_igv, $plazoMeses, $cuota);
            $cuotaNeta = bcadd($capitalPago, $interesSinIGV, 6);
            $cuotaTotal = $cuotaNeta;
            $saldoFinal = bcsub($saldoInicial, $capitalPago, 6);
            
            if ($cuota === $plazoMeses && bccomp($saldoFinal, '0.01', 2) < 0) {
                $capitalPago = $saldoInicial;
                $interesSinIGV = bcmul($capitalPago, $tem_sin_igv, 6);
                $cuotaNeta = bcadd($capitalPago, $interesSinIGV, 6);
                $cuotaTotal = $cuotaNeta;
                $saldoFinal = '0.00';
            }
            
            $fechaVcmto = $fechaInicio->copy()->addMonths($cuota - 1)->format('d/m/Y');
            
            $pagos[] = [
                'cuota' => $cuota,
                'vcmto' => $fechaVcmto,
                'saldo_inicial' => $this->bcround($saldoInicial, 2),
                'capital' => $this->bcround($capitalPago, 2),
                'interes' => $this->bcround($interesSinIGV, 2),
                'cuota_neta' => $this->bcround($cuotaNeta, 2),
                'igv' => $this->bcround($igv, 2),
                'total_cuota' => $this->bcround($cuotaTotal, 2),
                'saldo_final' => $this->bcround($saldoFinal, 2),
            ];
            
            $saldoInicial = $saldoFinal;
        }
        
        $total = count($pagos);
        $offset = ($page - 1) * $perPage;
        $paginatedPagos = array_slice($pagos, $offset, $perPage);
        
        return [
            'cliente' => 'CLIENTE SIMULACION',
            'monto_solicitado' => $this->bcround($capital, 2),
            'plazo_credito' => $plazoMeses,
            'tasa_efectiva_mensual' => number_format($property->tem, 4, '.', '') . '%',
            'tasa_efectiva_anual' => number_format($property->tea, 4, '.', '') . '%',
            'fecha_desembolso' => $fechaDesembolso . ' referencial',
            'cronograma_final' => [
                'plazo_total' => $plazoMeses,
                'moneda' => $moneda,
                'capital_otorgado' => $simbolo . ' ' . number_format((float)$capital, 2, '.', ','),
                'tea_compensatoria' => number_format($property->tea, 2, '.', '') . ' %',
                'tem_compensatoria' => number_format($property->tem, 2, '.', '') . ' %',
                'pagos' => $paginatedPagos,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ]
        ];
    }
    
    private function calcularCapitalPagoBC(string $capital, string $tem, int $plazo, int $periodo): string
    {
        $unoPlusTem = bcadd('1', $tem, 6);
        $factor = bcpow($unoPlusTem, (string)$plazo, 6);
        $numerador = bcmul($capital, bcmul($tem, $factor, 6), 6);
        $denominador = bcsub($factor, '1', 6);
        $cuota = bcdiv($numerador, $denominador, 6);
        
        $saldo = $capital;
        for ($i = 1; $i < $periodo; $i++) {
            $interes = bcmul($saldo, $tem, 6);
            $capitalPago = bcsub($cuota, $interes, 6);
            $saldo = bcsub($saldo, $capitalPago, 6);
        }
        
        $interes = bcmul($saldo, $tem, 6);
        return bcsub($cuota, $interes, 6);
    }
}