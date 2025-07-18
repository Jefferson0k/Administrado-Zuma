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
        $banks = ['BCP', 'Interbank', 'BBVA', 'Caja Arequipa', 'Caja Cusco', 'Ban Bif'];
        $accountType = ['Ahorros', 'Corriente'];
        $currency = Currency::cases();

        $faker = Factory::create();
        $faker_locale = Factory::create("es_PE");

        for ($i = 0; $i < 20; $i++) {
            BankAccount::create([
                "bank" => $faker->randomElement($banks),
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
