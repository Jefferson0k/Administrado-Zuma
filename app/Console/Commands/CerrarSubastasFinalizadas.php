<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Investment;
use App\Models\Bid;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;

class CerrarSubastasFinalizadas extends Command
{
    protected $signature = 'subastas:cerrar';
    protected $description = 'Cierra subastas vencidas, asigna ganador y devuelve fondos a perdedores';

    public function handle()
    {
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
            // Usar transacción para asegurar consistencia
            DB::transaction(function () use ($subasta) {
                $this->procesarSubasta($subasta);
            });
        }

        $this->info("Proceso completado. Se cerraron " . count($subastas) . " subastas.");
        return 0;
    }

    private function procesarSubasta($subasta)
    {
        // Obtener todas las inversiones de esta propiedad
        $inversiones = Investment::where('property_id', $subasta->property_id)->get();

        $totalInversiones = $inversiones->count();
        $this->info("Subasta {$subasta->id} (Propiedad {$subasta->property_id}) tiene {$totalInversiones} inversiones");

        if ($totalInversiones === 0) {
            $this->info("No se encontraron inversiones para la propiedad {$subasta->property_id}");
            $subasta->update(['estado' => 'finalizada']);
            return;
        }

        // Encontrar la inversión ganadora (mayor monto)
        $inversionGanadora = $inversiones->sortByDesc('monto_invertido')->first();
        $ganadorId = $inversionGanadora->user_id;

        // Crear el bid ganador
        $bidGanador = Bid::create([
            'auction_id' => $subasta->id,
            'user_id' => $ganadorId,
            'monto' => $inversionGanadora->monto_invertido
        ]);

        $this->info("Inversión ganadora: Monto {$inversionGanadora->monto_invertido}, Usuario {$ganadorId}");
        $this->info("Registro creado en bids: ID {$bidGanador->id}");

        // Devolver fondos SOLO a los usuarios que NO ganaron (perdedores)
        $inversionesPerdedoras = $inversiones->where('user_id', '!=', $ganadorId);
        
        foreach ($inversionesPerdedoras as $inversion) {
            $usuario = User::find($inversion->user_id);
            
            // Devolver el monto invertido al usuario perdedor
            $usuario->increment('monto', $inversion->monto_invertido);
            
            $this->info("💰 DEVUELTO S/ {$inversion->monto_invertido} al usuario {$usuario->id} ({$usuario->name}) - PERDEDOR");
        }

        // El ganador NO recibe devolución (ya tiene el dinero descontado desde que invirtió)
        $this->info("🏆 GANADOR: Usuario {$ganadorId} - NO recibe devolución (dinero ya descontado: S/ {$inversionGanadora->monto_invertido})");

        // Actualizar la subasta
        $subasta->update([
            'estado' => 'finalizada',
            'ganador_id' => $ganadorId
        ]);

        $this->info("Subasta {$subasta->id} cerrada exitosamente.");
        $this->info("🏆 Ganador: Usuario {$ganadorId} (pierde S/ {$inversionGanadora->monto_invertido})");
        $this->info("💰 Fondos devueltos a " . $inversionesPerdedoras->count() . " usuarios perdedores");
        
        // Opcional: Registrar estadísticas
        $this->registrarEstadisticas($subasta, $inversiones, $inversionGanadora);
    }

    private function registrarEstadisticas($subasta, $inversiones, $inversionGanadora)
    {
        $totalInvertido = $inversiones->sum('monto_invertido');
        $totalDevuelto = $inversiones->where('user_id', '!=', $inversionGanadora->user_id)
                                   ->sum('monto_invertido');
        
        $this->info("=== ESTADÍSTICAS DE SUBASTA {$subasta->id} ===");
        $this->info("Total participantes: " . $inversiones->count());
        $this->info("Total invertido: S/ {$totalInvertido}");
        $this->info("Monto ganador: S/ {$inversionGanadora->monto_invertido}");
        $this->info("Total devuelto: S/ {$totalDevuelto}");
        $this->info("========================================");
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyMinute();
    }
}