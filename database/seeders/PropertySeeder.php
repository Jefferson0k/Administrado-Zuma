<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        Property::create([
            'nombre' => 'Departamento San Borja',
            'distrito' => 'San Borja',
            'descripcion' => 'Departamento amplio con vista al parque',
            'validado' => true,
            'fecha_inversion' => '2025-06-26',
            'valor_estimado' => 27500,
            'currency_id' => 2,
            'deadlines_id' => 6,
            'tea' => 24.0,
            'tem' => 1.8088,
            'estado' => 'no_subastada',
        ]);
    }
}
