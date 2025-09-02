<?php

namespace App\Http\Resources\Subastas\PropertyLoanDetail;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;

class PropertyLoanDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $config = $this->property->configuraciones()
            ->with('plazo')
            ->latest()
            ->first();

        return [
            'id' => $this->id,
            'investor_id' => $this->investor_id,
            'inversionista' => [
                'documento' => $this->investor->document ?? null,
                'nombre_completo' => trim(
                    ($this->investor->name ?? '') . ' ' .
                    ($this->investor->first_last_name ?? '') . ' ' .
                    ($this->investor->second_last_name ?? '')
                ),
                'email' => $this->investor->email ?? null,
                'telefono' => $this->investor->telephone ?? null,
            ],
            'ocupacion_profesion' => $this->ocupacion_profesion,
            'motivo_prestamo' => $this->motivo_prestamo,
            'descripcion_financiamiento' => $this->descripcion_financiamiento,
            'solicitud_prestamo_para' => $this->solicitud_prestamo_para,
            'garantia' => $this->garantia,
            'perfil_riesgo' => $this->perfil_riesgo,
            'Property' => $this->property->nombre,
            'Plazo' => $config?->plazo?->nombre ?? '-',
            'Esquema' => $this->getEsquemaDetalle($config?->tipo_cronograma),
            
            // Usar librería Money para mostrar montos correctamente
            'Monto' => $this->formatMoneyWithCurrency($this->property->valor_requerido, $this->property->currency),
            'Monto_raw' => $this->formatMoney($this->property->valor_requerido), // Solo número
            'Valor_Estimado' => $this->formatMoneyWithCurrency($this->property->valor_estimado, $this->property->currency),
            
            'riesgo' => $config?->riesgo ?? 'medio',
            
            // Convertir tasas enteras a decimales para mostrar
            'tea' => $config?->tea ? number_format($config->tea / 100, 3, '.', '') . '%' : null, // 1550 -> "15.500%"
            'tem' => $config?->tem ? number_format($config->tem / 100, 3, '.', '') . '%' : null, // 125 -> "1.250%"
            'tea_raw' => $config?->tea ? $config->tea / 100 : null, // Para cálculos frontend
            'tem_raw' => $config?->tem ? $config->tem / 100 : null, // Para cálculos frontend
            
            'imagenes' => $this->property->getImagenes(),
            
            // Cronograma con formato Money
            'cronograma' => $this->property->paymentSchedules->map(function($item) {
                return [
                    'cuota' => $item->cuota,
                    'vencimiento' => Carbon::parse($item->vencimiento)->format('d-m-Y'),
                    'saldo_inicial' => $this->formatDecimal($item->saldo_inicial),
                    'capital' => $this->formatDecimal($item->capital),
                    'intereses' => $this->formatDecimal($item->intereses),
                    'cuota_neta' => $this->formatDecimal($item->cuota_neta),
                    'total_cuota' => $this->formatDecimal($item->total_cuota),
                    'saldo_final' => $this->formatDecimal($item->saldo_final),
                    'estado' => $item->estado,
                    // Formato con símbolo de moneda
                    'saldo_inicial_formatted' => $this->property->currency->simbolo . ' ' . number_format($item->saldo_inicial, 2, '.', ','),
                    'capital_formatted' => $this->property->currency->simbolo . ' ' . number_format($item->capital, 2, '.', ','),
                    'intereses_formatted' => $this->property->currency->simbolo . ' ' . number_format($item->intereses, 2, '.', ','),
                    'cuota_neta_formatted' => $this->property->currency->simbolo . ' ' . number_format($item->cuota_neta, 2, '.', ','),
                    'total_cuota_formatted' => $this->property->currency->simbolo . ' ' . number_format($item->total_cuota, 2, '.', ','),
                ];
            }),
            

            
            'logo' => url('/imagenes/cabecera.svg'),
            'hipotecas' => url('/imagenes/hipotecas.svg'),
            'principal' => url('/imagenes/principal.svg'),
        ];
    }

    /**
     * Convierte un objeto Money a decimal
     */
    private function formatMoney($money): float
    {
        if (!$money instanceof Money) {
            return 0.0;
        }
        
        $currencies = new ISOCurrencies();
        $formatter = new DecimalMoneyFormatter($currencies);
        return (float) $formatter->format($money);
    }

    /**
     * Formatea Money con símbolo de moneda
     */
    private function formatMoneyWithCurrency($money, $currency): string
    {
        if (!$money instanceof Money) {
            return $currency?->simbolo . ' 0.00';
        }
        
        $amount = $this->formatMoney($money);
        $simbolo = $currency?->simbolo ?? '';
        
        return $simbolo . ' ' . number_format($amount, 2, '.', ',');
    }

    /**
     * Formatea decimales consistentemente
     */
    private function formatDecimal($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * Obtiene descripción detallada del esquema
     */
    private function getEsquemaDetalle($tipoChronograma): string
    {
        return match($tipoChronograma) {
            'americano' => 'Sistema Americano (Solo Intereses)',
            'frances' => 'Sistema Francés (Cuotas Fijas)',
            default => 'Cuota fija'
        };
    }
}