<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CorporateEntity;
use App\Models\AmountRange;
use App\Models\TermPlan;
use App\Models\RateType;
use App\Models\FixedTermRate;

class CoopacInclusivaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear entidad
        $entidad = CorporateEntity::create([
            'nombre' => 'COOPAC INCLUSIVA',
            'ruc' => '20500000003',
            'direccion' => 'Av. Inclusiva 123',
            'telefono' => '987654323',
            'email' => 'info@coopacinclusiva.com',
            'tipo_entidad' => 'cooperativa',
            'estado' => 'activo',
        ]);

        // 2. Tipo de tasa
        $tea = RateType::firstOrCreate(['nombre' => 'TEA'], ['descripcion' => 'Tasa Efectiva Anual']);

        // 3. Crear rango único (para cualquier monto)
        $rango = AmountRange::create([
            'corporate_entity_id' => $entidad->id,
            'desde' => 1,
            'hasta' => null, // Sin límite superior
            'moneda' => 'PEN',
        ]);

        // 4. Crear plazos según la imagen
        $plazos = [
            365 => 'Hasta 12 meses',    // 11%
            730 => 'De 13 a 24 meses',  // 12%
            731 => 'Mayor a 24 meses',  // 13%
        ];

        $plazos_creados = [];
        foreach ($plazos as $dias => $nombre) {
            if ($dias == 365) {
                // Hasta 12 meses
                $plazos_creados[$dias] = TermPlan::firstOrCreate(
                    ['dias_minimos' => 1, 'dias_maximos' => 365],
                    ['nombre' => $nombre]
                );
            } elseif ($dias == 730) {
                // De 13 a 24 meses
                $plazos_creados[$dias] = TermPlan::firstOrCreate(
                    ['dias_minimos' => 366, 'dias_maximos' => 730],
                    ['nombre' => $nombre]
                );
            } else {
                // Mayor a 24 meses
                $plazos_creados[$dias] = TermPlan::firstOrCreate(
                    ['dias_minimos' => 731, 'dias_maximos' => 1825], // Hasta 5 años
                    ['nombre' => $nombre]
                );
            }
        }

        // 5. Crear tasas según la imagen
        $tasas = [
            365 => 11.00,  // Hasta 12 meses
            730 => 12.00,  // De 13 a 24 meses
            731 => 13.00,  // Mayor a 24 meses
        ];

        foreach ($tasas as $plazo_key => $tasa) {
            FixedTermRate::create([
                'corporate_entity_id' => $entidad->id,
                'amount_range_id' => $rango->id,
                'term_plan_id' => $plazos_creados[$plazo_key]->id,
                'rate_type_id' => $tea->id,
                'valor' => $tasa,
                'estado' => 'activo',
            ]);
        }
    }
}
