<?php

namespace App\Console\Commands;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ActivarSubastas extends Command
{
    protected $signature = 'subastas:activar';
    protected $description = 'Activa automáticamente subastas programadas';

    public function handle()
    {
        $ahora = Carbon::now();

        // Buscar propiedades programadas cuya subasta debe iniciar
        $subastas = Property::where('estado', 'programada')
            ->whereHas('subasta', function ($query) use ($ahora) {
                $query->where('estado', '!=', 'activa')
                      ->whereRaw("STR_TO_DATE(CONCAT(dia_subasta, ' ', hora_inicio), '%Y-%m-%d %H:%i:%s') <= ?", [$ahora]);
            })
            ->with('subasta')
            ->get();

        $activadas = 0;

        foreach ($subastas as $property) {
            $property->estado = 'en_subasta';
            $property->save();

            $property->subasta->estado = 'activa';
            $property->subasta->save();

            $activadas++;
        }

        $this->info("✔ Total activadas: {$activadas}");
    }
}
