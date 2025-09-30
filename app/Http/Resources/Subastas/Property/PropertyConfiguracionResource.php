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
            // Convertir enteros a decimales para el frontend
            'tea' => $this->tea !== null ? number_format($this->tea / 100, 3, '.', '') : null, // 1550 -> "15.500"
            'tem' => $this->tem !== null ? number_format($this->tem / 100, 3, '.', '') : null, // 125 -> "1.250"
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,
            // Añadimos los campos de detalle_inversionista_hipoteca
            'detalle_inversionista' => $this->detalleInversionistaHipoteca ? [
                'profesion_ocupacion' => $this->detalleInversionistaHipoteca->profesion_ocupacion,
                'fuente_ingreso'      => $this->detalleInversionistaHipoteca->fuente_ingreso,
                'ingreso_promedio'    => $this->detalleInversionistaHipoteca->ingreso_promedio,
            ] : null,

            'estado' => $this->estado,
            'estado_nombre' => $this->estado === 1 ? 'Inversionista' : ($this->estado === 2 ? 'Cliente' : 'Desconocido'),
            'nombre' => $this->property->nombre ?? '',
            'requerido' => $this->formatMoney($this->property->valor_requerido),
            'valor_estimado' => $this->formatMoney($this->property->valor_estimado),
            'deadlines_id' => $this->plazo->nombre ?? null,
            'descripcion' => $this->property->descripcion,
            'Moneda' => $this->property->currency->nombre ?? null,
            'foto' => $this->getImagenes(),
            'estado_property' => $this->property->estado,
            'estadoProperty' => match ($this->property->estado) {
                'en_subasta' => 'En subasta',
                'activa' => 'Activa',
                'subastada' => 'Subastada',
                'programada' => 'Programada',
                'desactivada' => 'Desactivada',
                'adquirido' => 'Adquirido',
                'pendiente' => 'Pendiente',
                'completo' => 'Completo',
                'espera' => 'Espera',
                default => 'Estado desconocido',
            },
            // Información adicional del cronograma
            'cronograma_info' => [
                'tipo' => $this->tipo_cronograma === 'americano' ? 'Americano (Solo Intereses)' : 'Francés (Cuotas Fijas)',
                'descripcion' => $this->tipo_cronograma === 'americano' 
                    ? 'Pago de intereses mensuales, capital al vencimiento' 
                    : 'Cuotas fijas con amortización creciente'
            ]
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