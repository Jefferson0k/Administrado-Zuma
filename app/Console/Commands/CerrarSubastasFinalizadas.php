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
use App\Models\PropertyConfiguracion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CerrarSubastasFinalizadas extends Command{
    protected $signature = 'subastas:cerrar';
    protected $description = 'Cierra subastas vencidas, asigna ganador, devuelve fondos a perdedores, actualiza estados y registra movimientos.';
    public function handle(){
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
    private function procesarSubasta($subasta){
        $pujas = Bid::where('auction_id', $subasta->id)->get();
        if ($pujas->isEmpty()) {
            $subasta->update(['estado' => 'finalizada']);
            $this->info("❌ Subasta {$subasta->id} finalizada sin participantes.");
            return;
        }
        $ganadorBid = $pujas->sortByDesc('monto')->first();
        $ganador = Investor::find($ganadorBid->investors_id);
        if (!$ganador) {
            $this->error("No se encontró el inversor ganador con ID: {$ganadorBid->investors_id}");
            return;
        }
        $property = $subasta->property;
        $currency = $property->currency->codigo ?? 'PEN';
        Movement::create([
            'investor_id' => $ganador->id,
            'amount' => $ganadorBid->monto,
            'type' => MovementType::INVESTMENT,
            'status' => MovementStatus::CONFIRMED,
            'confirm_status' => MovementStatus::CONFIRMED,
            'description' => "Zuma descontó el monto por ser ganador de la subasta {$subasta->id}",
            'currency' => $currency,
        ]);
        $balanceGanador = Balance::where('investor_id', $ganador->id)
            ->where('currency', $currency)
            ->first();
        if ($balanceGanador) {
            $balanceGanador->increment('invested_amount', $ganadorBid->monto);
        }
        $config = PropertyConfiguracion::where('property_id', $property->id)
            ->where('estado', 1)
            ->orderByDesc('id')
            ->first();
        if (!$config) {
            $this->error("❌ No se encontró configuración activa (estado = 1) para la propiedad {$property->id}");
            return;
        }
        $propertyInvestor = PropertyInvestor::where('config_id', $config->id)->first();
        if ($propertyInvestor) {
            $propertyInvestor->update([
                'investor_id' => $ganador->id,
            ]);
        } else {
            $this->error("❌ No se encontró PropertyInvestor con config_id {$config->id} para la propiedad {$property->id}");
            return;
        }
        $subasta->update([
            'estado' => 'finalizada',
            'ganador_id' => $ganador->id,
        ]);
        $property->update([
            'estado' => 'subastada',
        ]);
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
