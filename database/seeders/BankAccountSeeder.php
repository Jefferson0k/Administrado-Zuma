<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Models\BankAccount;
use App\Models\Investor;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $investorIDs = DB::table('investors')->pluck('id');
        $bankIDs = DB::table('banks')->pluck('id');
        $accountType = ['Ahorros', 'Corriente'];
        $currency = Currency::cases();

        $faker = Factory::create();
        $faker_locale = Factory::create("es_PE");

        for ($i = 0; $i < 20; $i++) {
            BankAccount::create([
                "bank_id" => $faker->randomElement($bankIDs),
                "type" => $faker->randomElement($accountType),
                "currency" => $faker->randomElement($currency)->value,
                "cc" => $faker->randomNumber(5, true),
                "cci" => $faker->randomNumber(5, true),
                "alias" => $faker->name(),
                "investor_id" => $faker->randomElement($investorIDs),
            ]);
        }
    }
}
