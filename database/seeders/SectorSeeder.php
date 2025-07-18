<?php

namespace Database\Seeders;

use App\Models\Sector;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();
        $sectors = ['Tecnología', 'Medicina', 'Construcción', 'Telecomunicaciones', 'Pesca', 'Alimentos'];

        foreach ($sectors as $sector) {
            Sector::create([
                'name' => $sector,
            ]);
        }
    }
}
