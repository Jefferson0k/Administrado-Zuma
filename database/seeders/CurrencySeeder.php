<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder{
    public function run(): void{
        Currency::insert([
            [
                'nombre' => 'Soles',
                'codigo' => 'PEN',
                'simbolo' => 'S/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Dólares',
                'codigo' => 'USD',
                'simbolo' => '$',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
