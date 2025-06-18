<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentFrequency;

class PaymentFrequencySeeder extends Seeder{
    public function run(): void{
        PaymentFrequency::insert([
            ['nombre' => 'Mensual', 'dias' => 30],
            ['nombre' => 'Bimestral', 'dias' => 60],
            ['nombre' => 'Trimestral', 'dias' => 90],
        ]);
    }
}