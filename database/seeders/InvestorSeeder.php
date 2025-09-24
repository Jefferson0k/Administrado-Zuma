<?php

namespace Database\Seeders;

use App\Models\Investor;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Provider\es_PE\Person;
use Faker\Provider\es_PE\Company;
use Illuminate\Support\Facades\Hash;

class InvestorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();
        $faker_locale = Factory::create('es_PE');

        // Create 30 investors as specified
        for ($i = 0; $i < 30; $i++) {
            $investor = Investor::create([
                'name' => $faker_locale->firstName(),
                'first_last_name' => $faker_locale->lastName(),
                'second_last_name' => $faker_locale->lastName(),
                'alias' => $faker_locale->word(),
                'document' => $faker_locale->unique()->numerify('########'), // 8 digits DNI
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password123@A'), // Secure password that meets requirements
                'telephone' => $faker->numerify('9########'), // 9 digits mobile number
                'status' => 'validated',
                'email_verified_at' => now(), // Set email as verified
            ]);

            // Create PEN and USD wallets (balances) for each investor
            $investor->createBalance('PEN', $faker->numberBetween(1000, 50000));
            $investor->createBalance('USD', $faker->numberBetween(1000, 50000));
        }
    }
}
