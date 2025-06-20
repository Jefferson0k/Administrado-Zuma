<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Investment;
use App\Models\Bid;
use App\Models\Customer;
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
            DB::transaction(function () use ($subasta) {
                $this->procesarSubasta($subasta);
            });
        }

        $this->info("Proceso completado. Se cerraron " . count($subastas) . " subastas.");
        return 0;
    }

    private function procesarSubasta($subasta)
    {
        $inversiones = Investment::where('property_id', $subasta->property_id)->get();
        $totalInversiones = $inversiones->count();
        $this->info("Subasta {$subasta->id} (Propiedad {$subasta->property_id}) tiene {$totalInversiones} inversiones");

        if ($totalInversiones === 0) {
            $this->info("No se encontraron inversiones para la propiedad {$subasta->property_id}");
            $subasta->update(['estado' => 'finalizada']);
            return;
        }

        $inversionGanadora = $inversiones->sortByDesc('monto_invertido')->first();
        $ganadorId = $inversionGanadora->customer_id;

        $bidGanador = Bid::create([
            'auction_id' => $subasta->id,
            'customer_id' => $ganadorId,
            'monto' => $inversionGanadora->monto_invertido
        ]);

        $this->info("Inversión ganadora: Monto {$inversionGanadora->monto_invertido}, Cliente {$ganadorId}");
        $this->info("Registro creado en bids: ID {$bidGanador->id}");

        $inversionesPerdedoras = $inversiones->where('customer_id', '!=', $ganadorId);

        foreach ($inversionesPerdedoras as $inversion) {
            $cliente = Customer::find($inversion->customer_id);
            $cliente->increment('monto', $inversion->monto_invertido);

            $this->info("💰 DEVUELTO S/ {$inversion->monto_invertido} al cliente {$cliente->id} ({$cliente->name}) - PERDEDOR");
        }

        $this->info("🏆 GANADOR: Cliente {$ganadorId} - NO recibe devolución (dinero ya descontado: S/ {$inversionGanadora->monto_invertido})");

        $subasta->update([
            'estado' => 'finalizada',
            'ganador_id' => $ganadorId
        ]);

        $this->info("Subasta {$subasta->id} cerrada exitosamente.");
        $this->info("🏆 Ganador: Cliente {$ganadorId} (pierde S/ {$inversionGanadora->monto_invertido})");
        $this->info("💰 Fondos devueltos a " . $inversionesPerdedoras->count() . " clientes perdedores");

        $this->registrarEstadisticas($subasta, $inversiones, $inversionGanadora);
    }

    private function registrarEstadisticas($subasta, $inversiones, $inversionGanadora)
    {
        $totalInvertido = $inversiones->sum('monto_invertido');
        $totalDevuelto = $inversiones->where('customer_id', '!=', $inversionGanadora->customer_id)
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
