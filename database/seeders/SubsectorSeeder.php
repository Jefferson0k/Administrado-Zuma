<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\Subsector;
use Illuminate\Database\Seeder;

class SubsectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subsectors = [
            'Tecnología' => [
                'Software',
                'Hardware',
                'Servicios de TI',
                'Telecomunicaciones',
                'E-commerce'
            ],
            'Medicina' => [
                'Equipos médicos',
                'Farmacéutica',
                'Servicios de salud',
                'Biotecnología',
                'Investigación médica'
            ],
            'Construcción' => [
                'Residencial',
                'Comercial',
                'Industrial',
                'Infraestructura',
                'Materiales'
            ],
            'Telecomunicaciones' => [
                'Telefonía móvil',
                'Internet',
                'Redes',
                'Equipos de comunicación',
                'Servicios digitales'
            ],
            'Pesca' => [
                'Pesca industrial',
                'Acuicultura',
                'Procesamiento',
                'Distribución',
                'Conservas'
            ],
            'Alimentos' => [
                'Procesados',
                'Bebidas',
                'Agricultura',
                'Distribución',
                'Restaurantes'
            ]
        ];

        foreach ($subsectors as $sectorName => $subsectorList) {
            $sector = Sector::where('name', $sectorName)->first();

            if ($sector) {
                foreach ($subsectorList as $subsectorName) {
                    Subsector::create([
                        'name' => $subsectorName,
                        'sector_id' => $sector->id
                    ]);
                }
            }
        }
    }
}
