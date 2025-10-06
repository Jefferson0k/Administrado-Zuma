<?php

namespace App\Http\Resources;

use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class PropertyConfiguracionSubastaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $solicitud = $this->solicitud; // ✅ relación belongsTo
        $subasta = $solicitud?->subasta;
        $inversionistas = collect(); // ✅ usamos colección

        if ($subasta) {
            $inversionistas = Bid::where('auction_id', $subasta->id)
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

            // ✅ Datos base desde solicitud
            'codigo_solicitud' => $solicitud?->codigo,
            'estado_solicitud' => $solicitud?->estado,
            'moneda' => $solicitud?->currency?->code,
            'inversionista' => $solicitud?->investor?->name,

            // ✅ Datos de subasta
            'subasta' => $subasta ? [
                'id' => $subasta->id,
                'monto_inicial' => $subasta->monto_inicial,
                'dia_subasta' => $subasta->dia_subasta,
                'hora_inicio' => $subasta->hora_inicio,
                'hora_fin' => $subasta->hora_fin,
                'tiempo_finalizacion' => $subasta->tiempo_finalizacion,
                'estado' => $subasta->estado,
            ] : null,

            // ✅ Inversionistas pujando
            'inversionistas_pujando' => $inversionistas,
            'total_inversionistas' => $inversionistas->count(),
            'monto_actual_mayor' => $inversionistas->isNotEmpty()
                ? $inversionistas->first()['monto']
                : ($subasta->monto_inicial ?? 0),

            // ✅ Campos financieros
            'tea' => $this->tea,
            'tem' => $this->tem,
            'tipo_cronograma' => $this->tipo_cronograma,
            'riesgo' => $this->riesgo,

            // ✅ Formatear valores monetarios
            'valor_requerido' => $this->formatMoneyValue($solicitud?->valor_requerido),
            'valor_general' => $this->formatMoneyValue($solicitud?->valor_general),

            // ✅ Imagenes
            'foto' => $this->getImagenesCorregido($solicitud),
        ];
    }

    /**
     * ✅ Formatear objetos Money o numéricos
     */
    private function formatMoneyValue($moneyObject): ?array
    {
        if (!$moneyObject) return null;

        try {
            if (is_object($moneyObject) && method_exists($moneyObject, 'getAmount')) {
                return [
                    'amount' => (float) $moneyObject->getAmount() / 100,
                    'currency' => $moneyObject->getCurrency()->getCode(),
                    'formatted' => $moneyObject->getCurrency()->getCode() . ' ' . number_format((float) $moneyObject->getAmount() / 100, 2, '.', ','),
                ];
            }

            if (is_numeric($moneyObject)) {
                return [
                    'amount' => (float) $moneyObject,
                    'currency' => 'PEN',
                    'formatted' => 'PEN ' . number_format((float) $moneyObject, 2, '.', ','),
                ];
            }

            return null;
        } catch (Exception $e) {
            Log::warning('Error al formatear monto', [
                'valor' => $moneyObject,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ Obtener imágenes asociadas
     */
    private function getImagenesCorregido($solicitud): array
    {
        $imagenes = [];

        try {
            // Si existe una relación con propiedad dentro de solicitud
            $property = $solicitud?->property ?? null;

            if ($property && method_exists($property, 'getImagenes')) {
                $imagenesModelo = $property->getImagenes();
                if (!empty($imagenesModelo)) {
                    return $imagenesModelo;
                }
            }

            // Fallback: buscar imágenes en carpeta pública
            if ($property && $property->id) {
                $rutaCarpeta = public_path("Propiedades/{$property->id}");

                if (File::exists($rutaCarpeta)) {
                    foreach (File::files($rutaCarpeta) as $archivo) {
                        $ext = strtolower($archivo->getExtension());
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                            $imagenes[] = [
                                'url' => asset("Propiedades/{$property->id}/" . $archivo->getFilename()),
                                'descripcion' => 'Imagen de la propiedad'
                            ];
                        }
                    }
                }
            }

            if (empty($imagenes)) {
                $imagenes[] = [
                    'url' => asset('Propiedades/no-image.png'),
                    'descripcion' => 'Sin imagen disponible'
                ];
            }

            return $imagenes;
        } catch (Exception $e) {
            Log::error("Error obteniendo imágenes para solicitud {$solicitud?->id}", [
                'error' => $e->getMessage()
            ]);

            return [[
                'url' => asset('Propiedades/no-image.png'),
                'descripcion' => 'Error al cargar imagen'
            ]];
        }
    }
}
