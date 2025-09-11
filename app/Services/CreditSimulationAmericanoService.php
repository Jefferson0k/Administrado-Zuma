<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Deadlines;
use Carbon\Carbon;
use Money\Money;

class CreditSimulationAmericanoService
{
    /**
     * Convierte un objeto Money a string decimal para BC Math
     */
    private function moneyToDecimalString(Money $money): string
    {
        $amount = $money->getAmount();
        return bcdiv($amount, '100', 6);
    }

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

    /**
     * Cronograma Americano: Solo intereses, capital al final
     */
    public function generate(Property $property, Deadlines $deadline, int $page = 1, int $perPage = 10): array
    {
        bcscale(6);
        
        // Convertir Money object a string decimal
        $capital = $this->moneyToDecimalString($property->valor_requerido);
        
        $plazoMeses = $deadline->duracion_meses;
        $moneda = $property->currency_id == 1 ? 'Soles' : 'Dólares';
        $simbolo = $property->currency_id == 1 ? 'PEN' : 'USD';
        
        // Convertir tasa entera a decimal: 125 -> 0.0125 (1.25%)
        $tem_decimal = bcdiv((string) $property->tem, '10000', 6);
        
        $fechaDesembolso = Carbon::now()->format('d/m/Y');
        $fechaInicio = Carbon::now()->addMonth()->day(15);
        
        $pagos = [];
        
        for ($cuota = 1; $cuota <= $plazoMeses; $cuota++) {
            // En sistema americano: solo intereses hasta la última cuota
            $interesSinIGV = bcmul($capital, $tem_decimal, 6);
            $igv = '0.00';
            
            // Capital solo en la última cuota
            $capitalPago = ($cuota === $plazoMeses) ? $capital : '0.00';
            
            $cuotaNeta = bcadd($interesSinIGV, $capitalPago, 6);
            $cuotaTotal = $cuotaNeta;
            
            // Saldo inicial siempre es el capital (no se amortiza hasta el final)
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
        
        // Convertir enteros a decimales para mostrar
        $tea_display = $property->tea / 100; // 1550 -> 15.50
        $tem_display = $property->tem / 100; // 125 -> 1.25
        
        return [
            'cliente' => 'CLIENTE SIMULACION',
            'monto_solicitado' => $this->bcround($capital, 2),
            'plazo_credito' => $plazoMeses,
            'tasa_efectiva_mensual' => number_format($tem_display, 3, '.', '') . '%',
            'tasa_efectiva_anual' => number_format($tea_display, 3, '.', '') . '%',
            'fecha_desembolso' => $fechaDesembolso . ' referencial',
            'cronograma_final' => [
                'plazo_total' => $plazoMeses,
                'moneda' => $moneda,
                'capital_otorgado' => $simbolo . ' ' . number_format((float)$capital, 2, '.', ','),
                'tea_compensatoria' => number_format($tea_display, 3, '.', '') . ' %',
                'tem_compensatoria' => number_format($tem_display, 3, '.', '') . ' %',
                'tipo_cronograma' => 'Americano (Solo Intereses)',
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