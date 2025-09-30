<?php

namespace Database\Seeders;

use App\Models\TipoInmueble;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoInmuebleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            ['nombre_tipo_inmueble' => 'Casa', 'orden_tipo_inmueble' => 1],
            ['nombre_tipo_inmueble' => 'Departamento', 'orden_tipo_inmueble' => 2],
            ['nombre_tipo_inmueble' => 'Edificio', 'orden_tipo_inmueble' => 3],
            ['nombre_tipo_inmueble' => 'Local Industrial', 'orden_tipo_inmueble' => 4],
            ['nombre_tipo_inmueble' => 'Local Comercial', 'orden_tipo_inmueble' => 5],
            ['nombre_tipo_inmueble' => 'Terreno', 'orden_tipo_inmueble' => 6],
        ];

        foreach ($tipos as $tipo) {
            TipoInmueble::create($tipo);
        }
    }
}
