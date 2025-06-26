<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Currency;
use App\Models\Deadlines;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder{
    public function run(): void{
        $currency = Currency::first();
        $deadline = Deadlines::first();

        if (!$currency || !$deadline) {
            $this->command->warn('⚠️ No se encontraron monedas o plazos. Seeder abortado.');
            return;
        }

        Property::factory()
            ->count(20)
            ->create([
                'currency_id' => $currency->id,
                'deadlines_id' => $deadline->id
            ]);
    }
}
