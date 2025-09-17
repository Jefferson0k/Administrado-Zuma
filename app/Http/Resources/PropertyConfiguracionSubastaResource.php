<?php

namespace App\Http\Resources;

use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class PropertyConfiguracionSubastaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $inversionistas = [];
        
        if ($this->subasta) {
            $inversionistas = Bid::where('auction_id', $this->subasta->id)
                ->with('investor')
                ->select(
                    'investors_id',
                    DB::raw('MAX(monto) as monto_maximo'),
                    DB::raw('MAX(created_at) as fecha_ultima_puja')
                )
                ->groupBy('investors_id')
                ->orderBy('monto_maximo', 'desc')
                ->get()
                ->map(function ($puja) {
                    return [
                        'nombre' => optional($puja->investor)->name ?? '-',
                        'fecha_inversion' => $puja->fecha_ultima_puja,
                        'monto' => $puja->monto_maximo,
                    ];
                });
        }

        return [
            'id' => $this->id,
            'nombre' => optional($this->property)->nombre,
            'departamento' => optional($this->property)->departamento,
            'provincia' => optional($this->property)->provincia,
            'distrito' => optional($this->property)->distrito,
            'descripcion' => optional($this->property)->descripcion,
            
            // ✅ Formatear valores Money correctamente
            'valor_requerido' => $this->formatMoneyValue(optional($this->property)->valor_requerido),
            'valor_estimado' => $this->formatMoneyValue(optional($this->property)->valor_estimado),
            'valor_subasta' => $this->formatMoneyValue(optional($this->property)->valor_subasta),
            
            'tea' => $this->tea,
            'tem' => $this->tem,
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,
            
            // ✅ Usar el método corregido para imágenes
            'foto' => $this->getImagenesCorregido(),
            
            'subasta' => $this->subasta ? [
                'id' => $this->subasta->id,
                'monto_inicial' => $this->subasta->monto_inicial,
                'dia_subasta' => $this->subasta->dia_subasta,
                'hora_inicio' => $this->subasta->hora_inicio,
                'hora_fin' => $this->subasta->hora_fin,
                'tiempo_finalizacion' => $this->subasta->tiempo_finalizacion,
                'estado' => $this->subasta->estado,
            ] : null,
            
            'inversionistas_pujando' => $inversionistas,
            'total_inversionistas' => count($inversionistas),
            'monto_actual_mayor' => $inversionistas->isNotEmpty()
                ? $inversionistas->first()['monto']
                : ($this->subasta->monto_inicial ?? 0),
            'property_investor_id' => optional($this->propertyInvestor)->id,
        ];
    }

    /**
     * ✅ Formatear objetos Money para la API
     */
    private function formatMoneyValue($moneyObject): ?array
    {
        if (!$moneyObject) {
            return null;
        }

        try {
            // Si es un objeto Money
            if (method_exists($moneyObject, 'getAmount') && method_exists($moneyObject, 'getCurrency')) {
                return [
                    'amount' => (float) $moneyObject->getAmount() / 100, // Convertir centavos a unidades
                    'currency' => $moneyObject->getCurrency()->getCode(),
                    'formatted' => $moneyObject->getCurrency()->getCode() . ' ' . number_format((float) $moneyObject->getAmount() / 100, 2, '.', ',')
                ];
            }
            
            // Si es un valor numérico (fallback)
            if (is_numeric($moneyObject)) {
                return [
                    'amount' => (float) $moneyObject / 100,
                    'currency' => 'PEN',
                    'formatted' => 'PEN ' . number_format((float) $moneyObject / 100, 2, '.', ',')
                ];
            }
            
            return null;
        } catch (Exception $e) {
            Log::warning('Error formatting money value', [
                'value' => $moneyObject,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ Método corregido para obtener imágenes
     * Usar tanto el método del modelo como fallback a carpeta pública
     */
    private function getImagenesCorregido(): array
    {
        $imagenes = [];

        try {
            // Primero intentar usar el método del modelo Property
            if ($this->property && method_exists($this->property, 'getImagenes')) {
                $imagenesModelo = $this->property->getImagenes();
                if (!empty($imagenesModelo)) {
                    return $imagenesModelo;
                }
            }

            // Fallback: buscar en carpeta pública (método original)
            if ($this->property && $this->property->id) {
                $rutaCarpeta = public_path("Propiedades/{$this->property->id}");
                
                if (File::exists($rutaCarpeta)) {
                    $archivos = File::files($rutaCarpeta);
                    foreach ($archivos as $archivo) {
                        // Validar que sea una imagen
                        $extension = strtolower($archivo->getExtension());
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                            $imagenes[] = [
                                'url' => asset("Propiedades/{$this->property->id}/" . $archivo->getFilename()),
                                'descripcion' => 'Imagen de la propiedad'
                            ];
                        }
                    }
                }
            }

            // Si no hay imágenes, devolver imagen por defecto
            if (empty($imagenes)) {
                $imagenes[] = [
                    'url' => asset('Propiedades/no-image.png'),
                    'descripcion' => 'Sin imagen disponible'
                ];
            }

            return $imagenes;
            
        } catch (Exception $e) {
            Log::error("Error obteniendo imágenes para propiedad configuración {$this->id}", [
                'property_id' => $this->property->id ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            
            return [[
                'url' => asset('Propiedades/no-image.png'),
                'descripcion' => 'Error al cargar imagen'
            ]];
        }
    }
}