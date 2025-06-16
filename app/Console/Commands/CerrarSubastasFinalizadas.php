<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Investment;
use App\Models\Bid;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class CerrarSubastasFinalizadas extends Command{
    protected $signature = 'subastas:cerrar';
    protected $description = 'Cierra subastas vencidas y asigna ganador';
    public function handle(){
        $now = Carbon::now();
        $subastas = Auction::where('estado', 'activa')
            ->where(function ($query) use ($now) {
                $query->where('dia_subasta', '<', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->where('dia_subasta', $now->toDateString())
                            ->where('hora_fin', '<=', $now->toTimeString());
                    });
            })
            ->get();

        foreach ($subastas as $subasta) {
            $inversionMayor = Investment::where('property_id', $subasta->property_id)
                ->orderByDesc('monto_invertido')
                ->first();

            $totalInversiones = Investment::where('property_id', $subasta->property_id)->count();
            $this->info("Subasta {$subasta->id} (Propiedad {$subasta->property_id}) tiene {$totalInversiones} inversiones");

            $ganadorId = null;
            if ($inversionMayor) {
                $ganadorId = $inversionMayor->user_id;
                $subasta->ganador_id = $ganadorId;
                
                $bidGanador = Bid::create([
                    'auction_id' => $subasta->id,
                    'user_id' => $ganadorId,
                    'monto' => $inversionMayor->monto_invertido
                ]);
                
                $this->info("Inversión mayor: Monto {$inversionMayor->monto_invertido}, Usuario {$inversionMayor->user_id}");
                $this->info("Registro creado en bids: ID {$bidGanador->id}");
            } else {
                $this->info("No se encontraron inversiones para la propiedad {$subasta->property_id}");
                
                if (!$subasta->property_id) {
                    $this->error("PROBLEMA: La subasta {$subasta->id} no tiene property_id asignado");
                }
            }

            $subasta->estado = 'finalizada';
            $subasta->save();

            $this->info("Subasta {$subasta->id} cerrada. Ganador ID: " . ($ganadorId ?? 'ninguno'));
            $this->info("Estado actualizado a: {$subasta->estado}, Ganador guardado: " . ($subasta->ganador_id ?? 'null'));
        }

        $this->info("Proceso completado. Se cerraron " . count($subastas) . " subastas.");
        return 0;
    }
    public function schedule(Schedule $schedule): void{
        $schedule->command(static::class)->everyMinute();
    }
}