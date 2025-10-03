<?php

namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\File;
use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;

class PropertyConfiguracionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $solicitud = $this->solicitud;
        $property  = $this->property ?? null; // si existe alguna relación property

        return [
            'id'              => $this->id,
            'solicitud_id'    => $this->solicitud_id,

            // 🔹 Convertir a float con 3 decimales, no string
            'tea'             => $this->tea !== null ? round((float) $this->tea, 3) : null,
            'tem'             => $this->tem !== null ? round((float) $this->tem, 3) : null,

            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo'          => $this->riesgo,
            'estado'          => $this->estado,
            'estado_nombre'   => $this->estado === 1 
                                    ? 'Inversionista' 
                                    : ($this->estado === 2 ? 'Cliente' : 'Desconocido'),

            // Campos provenientes de Solicitud o Property
            'nombre'          => $property->nombre ?? $solicitud->codigo ?? '',
            'requerido'       => $property 
                                    ? $this->formatMoney($property->valor_requerido) 
                                    : $this->formatMoney($solicitud->valor_requerido),
            'valor_estimado'  => $property 
                                    ? $this->formatMoney($property->valor_estimado) 
                                    : $this->formatMoney($solicitud->valor_general),
            'deadlines_id'    => $this->plazo->nombre ?? null,
            'Moneda'          => $property->currency->nombre ?? $solicitud->currency->nombre ?? null,
            'foto'            => $property ? $this->getImagenes() : [asset('Propiedades/no-image.png')],
            
            'estadoProperty'  => match ($this->solicitud->estado ?? '') {
                'en_subasta'  => 'En subasta',
                'activa'      => 'Activa',
                'subastada'   => 'Subastada',
                'programada'  => 'Programada',
                'desactivada' => 'Desactivada',
                'adquirido'   => 'Adquirido',
                'pendiente'   => 'Pendiente',
                'completo'    => 'Completo',
                'espera'      => 'Espera',
                default       => 'Estado desconocido',
            },

            'cronograma_info' => [
                'tipo'        => $this->tipo_cronograma === 'americano' 
                                    ? 'Americano (Solo Intereses)' 
                                    : 'Francés (Cuotas Fijas)',
                'descripcion' => $this->tipo_cronograma === 'americano' 
                                    ? 'Pago de intereses mensuales, capital al vencimiento' 
                                    : 'Cuotas fijas con amortización creciente'
            ]
        ];
    }

    private function formatMoney($money): float
    {
        if (!$money instanceof Money) {
            return (float) $money ?? 0.0;
        }

        $currencies = new ISOCurrencies();
        $formatter  = new DecimalMoneyFormatter($currencies);
        return (float) $formatter->format($money);
    }

    private function getImagenes(): array
    {
        $propertyId = $this->property->id ?? null;
        $imagenes   = [];

        if (!$propertyId) return [asset('Propiedades/no-image.png')];

        $rutaCarpeta = public_path("Propiedades/{$propertyId}");
        if (File::exists($rutaCarpeta)) {
            foreach (File::files($rutaCarpeta) as $archivo) {
                $imagenes[] = asset("Propiedades/{$propertyId}/" . $archivo->getFilename());
            }
        }

        if (empty($imagenes)) $imagenes[] = asset('Propiedades/no-image.png');

        return $imagenes;
    }
}
