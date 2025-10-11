++++++++6<?php

namespace Database\Seeders;

use App\Models\Exchange;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\MoneyConverter;

class ExchangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, let's make sure we start with a clean slate
        DB::table('exchanges')->delete();
        echo "Cleaned exchanges table\n";

        // Create exchanges one by one to ensure proper value conversion
        $this->createExchange(3.65, 3.75, true); // Active exchange
        $this->createExchange(3.70, 3.80);
        $this->createExchange(3.55, 3.65);
        $this->createExchange(3.60, 3.70);
        $this->createExchange(3.50, 3.60);
    }

    private function createExchange(float $buyRate, float $sellRate, bool $isActive = false): void
    {
        try {
            DB::beginTransaction();

            $exchange = new Exchange();
            $exchange->currency = 'USD';
            $exchange->exchange_rate_buy = $buyRate;
            $exchange->exchange_rate_sell = $sellRate;
            $exchange->status = $isActive ? 'active' : 'inactive';
            $exchange->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
