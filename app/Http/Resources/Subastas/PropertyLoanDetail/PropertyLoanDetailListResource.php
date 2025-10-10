<?php

namespace App\Http\Resources\Subastas\PropertyLoanDetail;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;

class PropertyLoanDetailListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $solicitud = $this->solicitud;
        $config = $this->configuracion;
        $investor = $this->investor;

        // Formateador de Money
        $currencies = new ISOCurrencies();
        $moneyFormatter = new DecimalMoneyFormatter($currencies);

        $formatMoney = fn(?Money $money) => $money ? $moneyFormatter->format($money) : '0.00';
        $formatCurrency = fn(?Money $money, $symbol = 'S/') => $money ? $symbol . ' ' . number_format((float)$money->getAmount()/100, 2, '.', ',') : $symbol . ' 0.00';

        return [
            'id' => $this->id,
            'estado_conclusion' => $this->estado_conclusion,
            'approval1_status'   => $this->approval1_status,
            'approval1_by' => $this->approval1User
                ? ($this->approval1User->name . ' ' . $this->approval1User->apellidos)
                : null,
            'approval1_at' => $this->approval1_at 
                ? Carbon::parse($this->approval1_at)->format('d/m/Y H:i:s A')
                : null,

            'solicitud_id' => $this->solicitud_id,
            'codigo_solicitud' => $solicitud?->codigo ?? 'Sin código', // Código de la solicitud
            'investor_id' => $this->investor_id,
            'motivo_prestamo' => $this->motivo_prestamo,
            'descripcion_financiamiento' => $this->descripcion_financiamiento,
            'solicitud_prestamo_para' => $this->solicitud_prestamo_para,
            'empresa_tasadora' => $this->empresa_tasadora,
            'config_id' => $this->config_id,

            // Datos del inversionista
            'documento' => $investor?->document ?? 'Sin documento',
            'cliente' => $investor
                ? trim("{$investor->name} {$investor->first_last_name} {$investor->second_last_name}")
                : 'Sin nombre',

            // Montos desde Solicitud
            'valor_general' => $formatCurrency($solicitud?->valor_general, $solicitud?->currency?->simbolo ?? 'S/'),
            'valor_requerido' => $formatCurrency($solicitud?->valor_requerido, $solicitud?->currency?->simbolo ?? 'S/'),

            // Configuración
            'riesgo' => $config?->riesgo ?? 'No asignado',
            'cronograma' => $config?->tipo_cronograma ?? 'No definido',
            'plazo' => $config?->plazo?->nombre ?? 'Sin plazo asignado',

            // Estado ahora viene de solicitud
            'estado' => $solicitud?->estado ?? 'sin_estado',
            'estado_nombre' => match ($solicitud?->estado) {
                'en_subasta' => 'En subasta',
                'activa' => 'Activa',
                'subastada' => 'Subastada',
                'programada' => 'Programada',
                'desactivada' => 'Desactivada',
                'adquirido' => 'Adquirido',
                'pendiente' => 'Pendiente',
                'espera' => 'Espera',
                'completo' => 'Completo',
                null => 'Sin estado',
                default => 'Estado desconocido',
            },
        ];
    }
}
