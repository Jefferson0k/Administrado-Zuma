<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder{
    public function run(): void{
        $currency = Currency::firstOrCreate(
            ['codigo' => 'USD'],
            [
                'nombre' => 'Dólar',
                'simbolo' => '$'
            ]
        );
        Property::create([
            'nombre'           => 'Departamento Miraflores',
            'distrito'         => 'Miraflores',
            'descripcion'      => 'Departamento moderno con vista al mar',
            'validado'         => true,
            'fecha_inversion'  => now()->addMonths(2),
            'valor_estimado'   => 150000,
            'valor_subasta'    => null,
            'currency_id'      => $currency->id,
            'tea'              => 12.0000,
            'tem'              => 0.9489,
            'estado'           => 'no_subastada',
        ]);
    }
}
