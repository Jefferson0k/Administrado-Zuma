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
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,

            // Se devuelven como string decimal (ej: "20.00")
            'tea' => $this->tea !== null ? (string) $this->tea : null,
            'tem' => $this->tem !== null ? (string) $this->tem : null,

            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,

            'estado' => $this->estado,
            'estado_nombre' => $this->estado === 1 ? 'Inversionista' : ($this->estado === 2 ? 'Cliente' : 'Desconocido'),

            'nombre' => $this->property->nombre,
            'requerido' => $this->formatMoney($this->property->valor_requerido),
            'valor_estimado' => $this->formatMoney($this->property->valor_estimado),

            'deadlines_id' => $this->plazo->nombre ?? null,
            'descripcion' => $this->property->descripcion,
            'Moneda' => $this->property->currency->nombre ?? null,

            'foto' => $this->getImagenes(),

            'estado' => $this->property->estado,
            'estadoProperty' => match ($this->property->estado) {
                'en_subasta'   => 'En subasta',
                'activa'       => 'Activa',
                'subastada'    => 'Subastada',
                'programada'   => 'Programada',
                'desactivada'  => 'Desactivada',
                'adquirido'    => 'Adquirido',
                'pendiente'    => 'Pendiente',
                'completo'     => 'Completo',
                'espera'       => 'Espera',
                default        => 'Estado desconocido',
            },
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
     * Obtiene imágenes de la propiedad
     */
    private function getImagenes(): array
    {
        $propertyId = $this->property->id ?? null;
        $imagenes = [];

        if (!$propertyId) {
            return [asset('Propiedades/no-image.png')];
        }

        $rutaCarpeta = public_path("Propiedades/{$propertyId}");

        if (File::exists($rutaCarpeta)) {
            $archivos = File::files($rutaCarpeta);
            foreach ($archivos as $archivo) {
                $imagenes[] = asset("Propiedades/{$propertyId}/" . $archivo->getFilename());
            }
        }

        if (empty($imagenes)) {
            $imagenes[] = asset('Propiedades/no-image.png');
        }

        return $imagenes;
    }
}
