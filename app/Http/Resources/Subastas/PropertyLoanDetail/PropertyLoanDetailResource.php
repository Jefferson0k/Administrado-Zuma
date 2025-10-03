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
        $solicitud = $this->solicitud;
        $investor = $this->investor;

        // Obtenemos el PropertyInvestor correspondiente a esta solicitud y configuración
        $propertyInvestor = $solicitud
            ->propertyInvestors()
            ->where('config_id', $this->config_id)
            ->first();

        // Obtenemos el DetalleInversionistaHipoteca basado en investor_id y config_id
        $detalleInversionista = \App\Models\DetalleInversionistaHipoteca::where('investor_id', $this->investor_id)
            ->where('configuracion_id', $this->config_id)
            ->first();

        // Obtener TODAS las propiedades de la solicitud con sus imágenes
        $propertiesWithImages = $solicitud->properties->map(function($property) use ($solicitud) {
            return [
                'property_id' => $property->id,
                'nombre' => $property->nombre,
                'direccion' => $property->direccion,
                'departamento' => $property->departamento,
                'provincia' => $property->provincia,
                'distrito' => $property->distrito,
                'descripcion' => $property->descripcion,
                'pertenece' => $property->pertenece,
                'valor_estimado' => $this->formatMoneyWithCurrency($property->valor_estimado, $solicitud->currency),
                'valor_requerido' => $this->formatMoneyWithCurrency($property->valor_requerido, $solicitud->currency),
                'tipo_inmueble' => $property->tipoInmueble->nombre_tipo_inmueble ?? null,
                'imagenes' => $this->getImagenesFromProperty($property)
            ];
        });

        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'investor_id' => $this->investor_id,

            // Datos del inversionista
            'inversionista' => [
                'documento' => $investor->document ?? null,
                'nombre_completo' => trim(($investor->name ?? '') . ' ' . ($investor->first_last_name ?? '') . ' ' . ($investor->second_last_name ?? '')),
            ],

            // Datos de préstamo
            'motivo_prestamo' => $this->motivo_prestamo,
            'descripcion_financiamiento' => $this->descripcion_financiamiento,
            'solicitud_prestamo_para' => $this->solicitud_prestamo_para,
            'empresa_tasadora' => $this->empresa_tasadora ?? null,
            'monto_tasacion' => $this->monto_tasacion ?? 0,
            'porcentaje_prestamo' => $this->porcentaje_prestamo ?? 0,
            'monto_invertir' => $this->monto_invertir ?? 0,
            'monto_prestamo' => $this->monto_prestamo ?? 0,

            // Montos desde Solicitud
            'monto_general' => $solicitud ? $this->formatMoneyWithCurrency($solicitud->valor_general, $solicitud->currency) : null,
            'monto_requerido' => $solicitud ? $this->formatMoneyWithCurrency($solicitud->valor_requerido, $solicitud->currency) : null,
            'fuente_ingreso'    => $solicitud->fuente_ingreso ?? null,
            'ingreso_promedio'    => $solicitud->ingreso_promedio ?? null,
            'profesion_ocupacion'    => $solicitud->profesion_ocupacion ?? null,

            // Configuración
            'plazo' => $this->configuracion?->plazo?->nombre ?? '-',
            'esquema' => $this->getEsquemaDetalle($this->configuracion?->tipo_cronograma),
            'riesgo' => $this->configuracion?->riesgo ?? 'medio',
            'tea' => $this->configuracion?->tea ? number_format($this->configuracion->tea / 100, 3, '.', '') . '%' : null,
            'tem' => $this->configuracion?->tem ? number_format($this->configuracion->tem / 100, 3, '.', '') . '%' : null,
            'tea_raw' => $this->configuracion?->tea ? $this->configuracion->tea / 100 : null,
            'tem_raw' => $this->configuracion?->tem ? $this->configuracion->tem / 100 : null,

            // TODAS las propiedades de la solicitud con sus datos completos
            'propiedades' => $propertiesWithImages,

            // Imágenes de todas las propiedades (para compatibilidad)
            'imagenes' => $solicitud 
                ? $solicitud->properties->flatMap(fn($p) => $this->getImagenesFromProperty($p)) 
                : [],

            // Cronograma desde PropertyInvestor
            'cronograma' => $propertyInvestor 
                ? $propertyInvestor->paymentSchedules->map(fn($item) => [
                    'cuota' => $item->cuota,
                    'vencimiento' => Carbon::parse($item->vencimiento)->format('d-m-Y'),
                    'saldo_inicial' => $this->formatDecimal($item->saldo_inicial),
                    'capital' => $this->formatDecimal($item->capital),
                    'intereses' => $this->formatDecimal($item->intereses),
                    'cuota_neta' => $this->formatDecimal($item->cuota_neta),
                    'total_cuota' => $this->formatDecimal($item->total_cuota),
                    'saldo_final' => $this->formatDecimal($item->saldo_final),
                    'estado' => $item->estado,
                ])->toArray()
                : [],

            // Recursos visuales
            'logo' => url('/imagenes/cabecera.svg'),
            'hipotecas' => url('/imagenes/hipotecas.svg'),
            'principal' => url('/imagenes/principal.svg'),
        ];
    }

    // Formatea Money en decimal
    private function formatMoney($money): float
    {
        if (!$money instanceof Money) {
            return 0.0;
        }

        $currencies = new ISOCurrencies();
        $formatter = new DecimalMoneyFormatter($currencies);
        return (float) $formatter->format($money);
    }

    // Formatea Money con símbolo de moneda
    private function formatMoneyWithCurrency($money, $currency): string
    {
        if (!$money instanceof Money) {
            return ($currency?->simbolo ?? '') . ' 0.00';
        }

        $amount = $this->formatMoney($money);
        $simbolo = $currency?->simbolo ?? '';
        return $simbolo . ' ' . number_format($amount, 2, '.', ',');
    }

    // Formatea decimal
    private function formatDecimal($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    // Convierte tipo cronograma a texto
    private function getEsquemaDetalle($tipoChronograma): string
    {
        return match($tipoChronograma) {
            'americano' => 'Sistema Americano',
            'frances' => 'Sistema Francés',
            default => 'Cuota fija',
        };
    }

    // Obtiene imágenes de una propiedad específica
    private function getImagenesFromProperty($property): array
    {
        if (!$property?->images) {
            return [];
        }

        return $property->images->map(fn($img) => [
            'url' => $img->path ? url("s3/{$img->path}") : asset('Propiedades/no-image.png'),
            'descripcion' => $img->description ?? '',
            'property_id' => $property->id // Agregar referencia a la propiedad
        ])->toArray();
    }
}