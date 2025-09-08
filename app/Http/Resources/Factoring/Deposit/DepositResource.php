<?php
namespace App\Http\Resources\Factoring\Deposit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            'bank_account_id' => $this->bank_account_id,
            'id_movimiento' => $this->movement->id,
            'nomBanco' => $this->bankAccount->bank->name ?? 'Sin banco',
            'nro_operation' => $this->nro_operation,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'investor' => $this->investor->name.' '.$this->investor->first_last_name.' '.$this->investor->second_last_name,
            'foto' => $this->resource_path,
            'estado' => $this->movement->status,
            'estadoConfig' => $this->movement->confirm_status,
            'estado_bank_account' => $this->bankAccount->status,
            'cc' => $this->bankAccount->cc,
            'cci' => $this->bankAccount->cci,
            'type' => $this->bankAccount->type,
            
            // Fechas originales formateadas
            'creacion' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'fecha_aprobacion_1' => $this->movement && $this->movement->aprobacion_1
                ? Carbon::parse($this->movement->aprobacion_1)->format('d-m-Y H:i:s A')
                : null,
            'fecha_aprobacion_2' => $this->movement && $this->movement->aprobacion_2
                ? Carbon::parse($this->movement->aprobacion_2)->format('d-m-Y H:i:s A')
                : null,
            
            // Fechas ISO para cálculos en frontend
            'creacion_iso' => $this->created_at,
            'fecha_aprobacion_1_iso' => $this->movement?->aprobacion_1,
            'fecha_aprobacion_2_iso' => $this->movement?->aprobacion_2,
            
            // Tiempos calculados en backend (opcional)
            'tiempo_hasta_aprobacion_1' => $this->calcularTiempoHastaAprobacion1(),
            'tiempo_entre_aprobaciones' => $this->calcularTiempoEntreAprobaciones(),
            'tiempo_total' => $this->calcularTiempoTotal(),
            
            // Información adicional de tiempo
            'tiempo_info' => [
                'creacion_a_aprobacion_1' => $this->obtenerInfoTiempo(
                    $this->created_at, 
                    $this->movement?->aprobacion_1
                ),
                'aprobacion_1_a_aprobacion_2' => $this->obtenerInfoTiempo(
                    $this->movement?->aprobacion_1, 
                    $this->movement?->aprobacion_2
                ),
                'total' => $this->obtenerInfoTiempo(
                    $this->created_at, 
                    $this->movement?->aprobacion_2
                ),
            ]
        ];
    }

    /**
     * Calcula el tiempo desde creación hasta aprobación 1 en minutos
     */
    private function calcularTiempoHastaAprobacion1(): ?int
    {
        if (!$this->movement || !$this->movement->aprobacion_1) {
            return null;
        }

        $fechaCreacion = Carbon::parse($this->created_at);
        $fechaAprobacion1 = Carbon::parse($this->movement->aprobacion_1);
        
        return $fechaCreacion->diffInMinutes($fechaAprobacion1);
    }

    /**
     * Calcula el tiempo entre aprobación 1 y aprobación 2 en minutos
     */
    private function calcularTiempoEntreAprobaciones(): ?int
    {
        if (!$this->movement || !$this->movement->aprobacion_1 || !$this->movement->aprobacion_2) {
            return null;
        }

        $fechaAprobacion1 = Carbon::parse($this->movement->aprobacion_1);
        $fechaAprobacion2 = Carbon::parse($this->movement->aprobacion_2);
        
        return $fechaAprobacion1->diffInMinutes($fechaAprobacion2);
    }

    /**
     * Calcula el tiempo total desde creación hasta aprobación 2 en minutos
     */
    private function calcularTiempoTotal(): ?int
    {
        if (!$this->movement || !$this->movement->aprobacion_2) {
            return null;
        }

        $fechaCreacion = Carbon::parse($this->created_at);
        $fechaAprobacion2 = Carbon::parse($this->movement->aprobacion_2);
        
        return $fechaCreacion->diffInMinutes($fechaAprobacion2);
    }

    /**
     * Obtiene información detallada del tiempo transcurrido
     */
    private function obtenerInfoTiempo($fechaInicio, $fechaFin): array
    {
        if (!$fechaInicio || !$fechaFin) {
            return [
                'minutos' => null,
                'texto' => '--',
                'categoria' => 'sin_datos',
                'color' => 'gray'
            ];
        }

        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        
        $minutos = $inicio->diffInMinutes($fin);
        $horas = $inicio->diffInHours($fin);
        $dias = $inicio->diffInDays($fin);

        // Formatear texto legible
        $texto = $this->formatearTiempo($minutos);
        
        // Categorizar y asignar color
        $categoria = $this->categorizarTiempo($minutos);
        $color = $this->obtenerColorTiempo($categoria);

        return [
            'minutos' => $minutos,
            'horas' => round($minutos / 60, 1),
            'dias' => round($minutos / (60 * 24), 1),
            'texto' => $texto,
            'categoria' => $categoria,
            'color' => $color
        ];
    }

    /**
     * Formatea el tiempo en texto legible
     */
    private function formatearTiempo(int $minutos): string
    {
        if ($minutos < 60) {
            return $minutos . ' min';
        }
        
        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;
        
        if ($horas < 24) {
            return $minutosRestantes > 0 
                ? "{$horas}h {$minutosRestantes}min" 
                : "{$horas}h";
        }
        
        $dias = floor($horas / 24);
        $horasRestantes = $horas % 24;
        
        if ($dias < 7) {
            return $horasRestantes > 0 
                ? "{$dias}d {$horasRestantes}h" 
                : "{$dias}d";
        }
        
        $semanas = floor($dias / 7);
        $diasRestantes = $dias % 7;
        
        return $diasRestantes > 0 
            ? "{$semanas}sem {$diasRestantes}d" 
            : "{$semanas}sem";
    }

    /**
     * Categoriza el tiempo según rangos
     */
    private function categorizarTiempo(int $minutos): string
    {
        if ($minutos <= 30) return 'muy_rapido';
        if ($minutos <= 180) return 'rapido';        // 3 horas
        if ($minutos <= 1440) return 'normal';       // 24 horas
        if ($minutos <= 4320) return 'lento';        // 3 días
        return 'muy_lento';
    }

    /**
     * Obtiene el color según la categoría
     */
    private function obtenerColorTiempo(string $categoria): string
    {
        return match($categoria) {
            'muy_rapido' => 'green',
            'rapido' => 'blue',
            'normal' => 'yellow',
            'lento' => 'orange',
            'muy_lento' => 'red',
            default => 'gray'
        };
    }
}