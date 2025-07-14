<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Deadlines;
class TermSeeder extends Seeder{
    public function run(): void{
        $terms = [
            ['duracion_meses' => 12],
            ['duracion_meses' => 18],
            ['duracion_meses' => 24],
            ['duracion_meses' => 36],
            ['duracion_meses' => 48],
            ['duracion_meses' => 60],
            ['duracion_meses' => 72],
        ];
        
        foreach ($terms as $term) {
            Deadlines::create([
                'nombre' => $term['duracion_meses'] . ' meses',
                'duracion_meses' => $term['duracion_meses'],
            ]);
        }
    }
}
