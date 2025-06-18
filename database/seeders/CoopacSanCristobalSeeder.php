<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CorporateEntity;
use App\Models\AmountRange;
use App\Models\TermPlan;
use App\Models\RateType;
use App\Models\FixedTermRate;

class CoopacSanCristobalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear entidad
        $entidad = CorporateEntity::create([
            'nombre' => 'COOPAC SAN CRISTOBAL',
            'ruc' => '20500000001',
            'direccion' => 'Av. Principal 123',
            'telefono' => '987654321',
            'email' => 'contacto@sancristobal.com',
            'tipo_entidad' => 'cooperativa',
            'estado' => 'activo',
        ]);

        // 2. Tipos de tasa
        $tea = RateType::firstOrCreate(['nombre' => 'TEA'], ['descripcion' => 'Tasa Efectiva Anual']);

        // 3. Rangos de monto
        $rango1 = AmountRange::create([
            'corporate_entity_id' => $entidad->id,
            'desde' => 500,
            'hasta' => 20999,
            'moneda' => 'PEN',
        ]);

        $rango2 = AmountRange::create([
            'corporate_entity_id' => $entidad->id,
            'desde' => 21000,
            'hasta' => null,
            'moneda' => 'PEN',
        ]);

        // 4. Plazos y tasas
        $plazos_y_tasas = [
            [90, 1.80, 2.00],
            [180, 2.00, 2.50],
            [270, 2.50, 3.00],
            [360, 3.00, 3.50],
            [540, 3.50, 4.00],
            [720, 4.50, 5.00],
            [1080, 5.00, 5.50],
            [1440, 5.50, 6.00],
            [1800, 6.00, 6.50],
        ];

        foreach ($plazos_y_tasas as [$dias, $tasaRango1, $tasaRango2]) {
            $plazo = TermPlan::firstOrCreate(
                ['dias_minimos' => $dias, 'dias_maximos' => $dias],
                ['nombre' => "$dias DÍAS"]
            );

            // Tasa para rango1
            FixedTermRate::create([
                'corporate_entity_id' => $entidad->id,
                'amount_range_id' => $rango1->id,
                'term_plan_id' => $plazo->id,
                'rate_type_id' => $tea->id,
                'valor' => $tasaRango1,
                'estado' => 'activo',
            ]);

            // Tasa para rango2
            FixedTermRate::create([
                'corporate_entity_id' => $entidad->id,
                'amount_range_id' => $rango2->id,
                'term_plan_id' => $plazo->id,
                'rate_type_id' => $tea->id,
                'valor' => $tasaRango2,
                'estado' => 'activo',
            ]);
        }
    }
}
