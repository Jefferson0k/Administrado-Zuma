<?php

namespace Database\Seeders;

use App\Models\Investor;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InvestorSeeder extends Seeder {
    public function run(): void {
        $faker = Factory::create();
        $faker_locale = Factory::create('es_PE');

        for ($i = 0; $i < 1; $i++) {
            $investor = Investor::create([
                'name' => $faker_locale->firstName(),
                'first_last_name' => $faker_locale->lastName(),
                'second_last_name' => $faker_locale->lastName(),
                'alias' => $faker_locale->word(),
                'document' => $faker_locale->unique()->numerify('########'),
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password123@A'),
                'telephone' => $faker->numerify('9########'),
                'status' => 'validated',
                'email_verified_at' => now(),
            ]);

            // Crear balances relacionados
            $investor->balances()->create([
                'currency' => 'PEN',
                'amount' => $faker->numberBetween(1000, 50000),
            ]);

            $investor->balances()->create([
                'currency' => 'USD',
                'amount' => $faker->numberBetween(1000, 50000),
            ]);
        }
    }
}
