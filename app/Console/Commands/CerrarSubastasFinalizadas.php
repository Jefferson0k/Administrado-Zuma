<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Balance;
use App\Models\Movement;
use App\Models\PropertyInvestor;
use App\Models\Investor;
use App\Enums\MovementStatus;
use App\Enums\MovementType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CerrarSubastasFinalizadas extends Command
{
    protected $signature = 'subastas:cerrar';
    protected $description = 'Cierra subastas vencidas, asigna ganador, devuelve fondos a perdedores, actualiza estados y registra movimientos.';

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
            })->get();

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
        $pujas = Bid::where('auction_id', $subasta->id)->get();
        if ($pujas->isEmpty()) {
            $subasta->update(['estado' => 'finalizada']);
            $this->info("❌ Subasta {$subasta->id} finalizada sin participantes.");
            return;
        }

        // Ganador: mayor monto
        $ganadorBid = $pujas->sortByDesc('monto')->first();
        $ganador = Investor::find($ganadorBid->investors_id);

        if (!$ganador) {
            $this->error("No se encontró el inversor ganador con ID: {$ganadorBid->investors_id}");
            return;
        }

        $property = $subasta->property;
        $currency = $property->currency->codigo ?? 'PEN'; // PEN o USD

        // Registrar movimiento de descuento (Zuma descuenta al ganador)
        Movement::create([
            'investor_id' => $ganador->id,
            'amount' => $ganadorBid->monto,
            'type' => MovementType::INVESTMENT,
            'status' => MovementStatus::CONFIRMED,
            'confirm_status' => MovementStatus::CONFIRMED,
            'description' => "Zuma descontó el monto por ser ganador de la subasta {$subasta->id}",
            'currency' => $currency,
        ]);

        // Actualizar balance del ganador (descontar de invested_amount)
        $balanceGanador = Balance::where('investor_id', $ganador->id)->where('currency', $currency)->first();
        if ($balanceGanador) {
            $balanceGanador->increment('invested_amount', $ganadorBid->monto);
        }

        // Asignar propiedad al ganador
        PropertyInvestor::create([
            'property_id' => $property->id,
            'investor_id' => $ganador->id,
        ]);

        // Actualizar subasta
        $subasta->update([
            'estado' => 'finalizada',
            'ganador_id' => $ganador->id,
        ]);

        // Actualizar estado de propiedad
        $property->update([
            'estado' => 'subastada',
        ]);

        // Devolver fondos a perdedores
        $perdedores = $pujas->where('investors_id', '!=', $ganador->id);
        foreach ($perdedores as $puja) {
            $balance = Balance::where('investor_id', $puja->investors_id)
                              ->where('currency', $currency)
                              ->first();

            if ($balance) {
                $balance->increment('amount', $puja->monto);
            }

            Movement::create([
                'investor_id' => $puja->investors_id,
                'amount' => $puja->monto,
                'type' => MovementType::WITHDRAW,
                'status' => MovementStatus::CONFIRMED,
                'confirm_status' => MovementStatus::CONFIRMED,
                'description' => "Devolución por perder subasta {$subasta->id}",
                'currency' => $currency,
            ]);
        }

        $this->info("✅ Subasta {$subasta->id} procesada. Ganador: {$ganador->id}, monto: {$ganadorBid->monto}");
        $this->info("💰 Se devolvieron fondos a " . $perdedores->count() . " perdedores.");
    }
}
