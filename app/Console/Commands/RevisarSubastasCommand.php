<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RevisarSubastasCommand extends Command
{
    protected $signature = 'subastas:revisar';
    protected $description = 'Revisar subastas vencidas sin inversiones y activar propiedad automáticamente';

    public function handle()
    {
        $ahora = Carbon::now();

        // Buscar solo subastas que ya vencieron
        $subastas = Auction::with(['property', 'property.investments', 'pujas'])
            ->where('estado', 'en_subasta')
            ->where(function ($q) use ($ahora) {
                $q->where('hora_fin', '<=', $ahora)
                  ->orWhere('tiempo_finalizacion', '<=', $ahora);
            })
            ->get();

        foreach ($subastas as $subasta) {
            $property = $subasta->property;

            if (!$property) {
                continue;
            }

            // Condiciones: propiedad en_subasta + no hay inversiones + no hay pujas
            if (
                $property->estado === 'en_subasta' &&
                $property->investments()->count() === 0 &&
                $subasta->pujas()->count() === 0
            ) {
                $property->estado = 'activa';
                $property->save();

                Log::info("✅ Propiedad {$property->id} cambiada a ACTIVA (subasta vencida sin inversiones).");
            }
        }

        $this->info('Revisión de subastas completada.');
    }
}
