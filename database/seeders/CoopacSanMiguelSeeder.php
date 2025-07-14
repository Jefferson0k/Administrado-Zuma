<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CorporateEntity;
use App\Models\AmountRange;
use App\Models\TermPlan;
use App\Models\RateType;
use App\Models\FixedTermRate;

class CoopacSanMiguelSeeder extends Seeder
{
    public function run(): void
    {
        $entidad = CorporateEntity::create([
            'nombre' => 'COOPAC SAN MIGUEL',
            'ruc' => '20500000002',
            'direccion' => 'Av. San Miguel 456',
            'telefono' => '987654322',
            'email' => 'info@coopacsanmiguel.com',
            'tipo_entidad' => 'cooperativa',
            'estado' => 'activo',
        ]);

        $tea = RateType::firstOrCreate(['nombre' => 'TEA'], ['descripcion' => 'Tasa Efectiva Anual']);

        $rangos = [
            [10000, 25000],
            [25001, 50000],
            [50001, 75000],
            [75001, 90000],
            [90001, 100000],
            [100001, null],
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

        $plazos = [
            90 => '90',
            180 => '180',
            360 => '360',
        ];

        $plazos_creados = [];
        foreach ($plazos as $dias => $label) {
            $plazos_creados[$dias] = TermPlan::firstOrCreate(
                ['dias_minimos' => $dias, 'dias_maximos' => $dias],
                ['nombre' => "$dias días"]
            );
        }

        $tasas = [
            [9.25, 9.00, 8.75],
            [9.50, 9.25, 9.00],
            [9.75, 9.50, 9.25],
            [10.00, 9.75, 9.50],
            [10.25, 10.00, 9.75],
            [10.50, 10.25, 10.00],
        ];

        foreach ($tasas as $index => [$tasa90, $tasa180, $tasa360]) {
            $rango = $rangos_creados[$index];

            FixedTermRate::create([
                'corporate_entity_id' => $entidad->id,
                'amount_range_id' => $rango->id,
                'term_plan_id' => $plazos_creados[90]->id,
                'rate_type_id' => $tea->id,
                'valor' => $tasa90,
                'estado' => 'activo',
            ]);

            FixedTermRate::create([
                'corporate_entity_id' => $entidad->id,
                'amount_range_id' => $rango->id,
                'term_plan_id' => $plazos_creados[180]->id,
                'rate_type_id' => $tea->id,
                'valor' => $tasa180,
                'estado' => 'activo',
            ]);

            FixedTermRate::create([
                'corporate_entity_id' => $entidad->id,
                'amount_range_id' => $rango->id,
                'term_plan_id' => $plazos_creados[360]->id,
                'rate_type_id' => $tea->id,
                'valor' => $tasa360,
                'estado' => 'activo',
            ]);
        }
    }
}
