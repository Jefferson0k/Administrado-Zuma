<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CorporateEntity;
use App\Models\AmountRange;
use App\Models\TermPlan;
use App\Models\RateType;
use App\Models\FixedTermRate;

class CoopacLosAndesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear entidad
        $entidad = CorporateEntity::create([
            'nombre' => 'COOPAC LOS ANDES',
            'ruc' => '20500000004',
            'direccion' => 'Av. Los Andes 789',
            'telefono' => '987654324',
            'email' => 'info@coopaclosandes.com',
            'tipo_entidad' => 'cooperativa',
            'estado' => 'activo',
        ]);

        // 2. Tipos de tasa
        $tea = RateType::firstOrCreate(['nombre' => 'TEA'], ['descripcion' => 'Tasa Efectiva Anual']);
        $tem = RateType::firstOrCreate(['nombre' => 'TEM'], ['descripcion' => 'Tasa Efectiva Mensual']);

        // 3. Crear rangos de monto según la tabla
        $rangos = [
            [100, 20000],
            [20001, 50000],
            [50001, 100000],
            [100001, null], // Mayor a 100,000
        ];

        $rangos_creados = [];
        foreach ($rangos as $rango) {
            $rangos_creados[] = AmountRange::create([
                'corporate_entity_id' => $entidad->id,
                'desde' => $rango[0],
                'hasta' => $rango[1],
                'moneda' => 'PEN',
            ]);
        }

        // 4. Crear plazos según la tabla
        $plazos = [
            90 => 'DPF de 90 Días',     // 3 meses
            180 => 'DPF de 180 Días',   // 6 meses
            270 => 'DPF de 270 Días',   // 9 meses
            360 => 'DPF de 360 Días',   // 1 año
            540 => 'DPF de 540 Días',   // 1.5 años
            720 => 'DPF de 720 Días',   // 2 años
            1080 => 'DPF de 1080 Días', // 3 años
            1440 => 'DPF de 1440 Días', // 4 años
            1800 => 'DPF de 1800 Días', // 5 años
        ];

        $plazos_creados = [];
        foreach ($plazos as $dias => $nombre) {
            $plazos_creados[$dias] = TermPlan::firstOrCreate(
                ['dias_minimos' => $dias, 'dias_maximos' => $dias],
                ['nombre' => $nombre]
            );
        }

        // 5. Tasas TEA según la tabla (primera parte de cada celda)
        $tasas_tea = [
            // Rango 1: S/ 100 a S/ 20,000
            [
                90 => 4.10, 180 => 5.10, 270 => 6.10, 360 => 8.00,
                540 => 8.50, 720 => 9.00, 1080 => 9.70, 1440 => 10.50, 1800 => 11.30
            ],
            // Rango 2: S/ 20,001 a S/ 50,000
            [
                90 => 3.40, 180 => 4.40, 270 => 5.40, 360 => 7.10,
                540 => 7.50, 720 => 8.00, 1080 => 8.70, 1440 => 9.50, 1800 => 10.30
            ],
            // Rango 3: S/ 50,001 a S/ 100,000
            [
                90 => 2.80, 180 => 5.00, 270 => 6.60, 360 => 7.00,
                540 => 7.00, 720 => 7.50, 1080 => 8.20, 1440 => 9.00, 1800 => 9.80
            ],
            // Rango 4: > S/ 100,000
            [
                90 => 3.00, 180 => 5.00, 270 => 6.60, 360 => 7.00,
                540 => 7.00, 720 => 7.50, 1080 => 8.20, 1440 => 9.00, 1800 => 9.80
            ],
        ];

        // 6. Tasas TEM según la tabla (segunda parte de cada celda)
        $tasas_tem = [
            // Rango 1: S/ 100 a S/ 20,000
            [
                90 => 0.34, 180 => 0.42, 270 => 0.49, 360 => 0.64,
                540 => 0.68, 720 => 0.72, 1080 => 0.77, 1440 => 0.84, 1800 => 0.90
            ],
            // Rango 2: S/ 20,001 a S/ 50,000
            [
                90 => 0.31, 180 => 0.36, 270 => 0.44, 360 => 0.57,
                540 => 0.60, 720 => 0.64, 1080 => 0.70, 1440 => 0.76, 1800 => 0.82
            ],
            // Rango 3: S/ 50,001 a S/ 100,000
            [
                90 => 0.25, 180 => 0.41, 270 => 0.53, 360 => 0.57,
                540 => 0.57, 720 => 0.60, 1080 => 0.66, 1440 => 0.72, 1800 => 0.78
            ],
            // Rango 4: > S/ 100,000
            [
                90 => 0.25, 180 => 0.41, 270 => 0.53, 360 => 0.57,
                540 => 0.57, 720 => 0.60, 1080 => 0.66, 1440 => 0.72, 1800 => 0.78
            ],
        ];

        // 7. Crear registros de tasas
        foreach ($tasas_tea as $index => $tasas_rango_tea) {
            $rango = $rangos_creados[$index];
            $tasas_rango_tem = $tasas_tem[$index];
            
            foreach ($tasas_rango_tea as $dias => $tasa_tea) {
                // Crear tasa TEA
                FixedTermRate::create([
                    'corporate_entity_id' => $entidad->id,
                    'amount_range_id' => $rango->id,
                    'term_plan_id' => $plazos_creados[$dias]->id,
                    'rate_type_id' => $tea->id,
                    'valor' => $tasa_tea,
                    'estado' => 'activo',
                ]);

                // Crear tasa TEM
                FixedTermRate::create([
                    'corporate_entity_id' => $entidad->id,
                    'amount_range_id' => $rango->id,
                    'term_plan_id' => $plazos_creados[$dias]->id,
                    'rate_type_id' => $tem->id,
                    'valor' => $tasas_rango_tem[$dias],
                    'estado' => 'activo',
                ]);
            }
        }
    }
}
