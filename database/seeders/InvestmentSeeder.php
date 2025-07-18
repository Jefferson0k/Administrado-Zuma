<?php

namespace Database\Seeders;

use App\Enums\MovementStatus;
use App\Enums\MovementType;
use App\Models\Balance;
use App\Models\Investment;
use App\Models\Invoice;
use App\Models\Investor;
use App\Models\Movement;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\MoneyConverter;

class InvestmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();

        // Get all investors and invoices
        $investors = Investor::all();
        $invoices = Invoice::where('status', 'active')->limit(20)->get();

        if ($investors->count() === 0 || $invoices->count() === 0) {
            return;
        }

        // First, let's give each investor some money in their balances
        foreach ($investors as $investor) {
            try {
                DB::beginTransaction();

                // Add PEN balance (between 100k and 500k)
                $penAmount = $faker->numberBetween(100000, 500000);
                $penBalance = Balance::firstOrCreate(
                    ['investor_id' => $investor->id, 'currency' => 'PEN'],
                    ['amount' => MoneyConverter::fromDecimal(0, 'PEN')]
                );
                $penBalance->amount = MoneyConverter::fromDecimal($penAmount, 'PEN');
                $penBalance->save();

                // Add USD balance (between 30k and 150k)
                $usdAmount = $faker->numberBetween(30000, 150000);
                $usdBalance = Balance::firstOrCreate(
                    ['investor_id' => $investor->id, 'currency' => 'USD'],
                    ['amount' => MoneyConverter::fromDecimal(0, 'USD')]
                );
                $usdBalance->amount = MoneyConverter::fromDecimal($usdAmount, 'USD');
                $usdBalance->save();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }

        // Now create investments for each invoice
        foreach ($invoices as $invoice) {
            // Each invoice will be invested by 2-4 random investors
            $numInvestors = $faker->numberBetween(2, 4);
            $selectedInvestors = $investors->random($numInvestors);

            // Decide what percentage of the invoice will be funded (between 50% and 100%)
            $totalFundingPercentage = $faker->numberBetween(50, 100);

            // Calculate random percentages that sum up to the total funding percentage
            $percentages = [];
            $remainingPercentage = $totalFundingPercentage;
            $investorsCount = count($selectedInvestors);

            foreach ($selectedInvestors as $index => $investor) {
                if ($index === $investorsCount - 1) {
                    // Last investor gets the remaining percentage
                    $percentages[] = $remainingPercentage;
                } else {
                    // Random percentage between 5% and remaining - 5% (to ensure all get something)
                    $maxPercentage = $remainingPercentage - (5 * ($investorsCount - $index - 1));
                    $percentage = $faker->numberBetween(5, max(5, $maxPercentage));
                    $percentages[] = $percentage;
                    $remainingPercentage -= $percentage;
                }
            }

            $totalAmount = $invoice->financed_amount;

            foreach ($selectedInvestors as $index => $investor) {
                try {
                    DB::beginTransaction();

                    // Get investor's balance for this currency
                    $balance = Balance::where('investor_id', $investor->id)
                        ->where('currency', $invoice->currency)
                        ->first();

                    // Calculate this investor's amount based on their percentage of the total amount
                    $percentage = $percentages[$index];
                    $investorAmount = $totalAmount->multiply(strval($percentage / 100));

                    if (!$balance || $balance->amount->lessThan($investorAmount)) {
                        continue;
                    }

                    // Create the investment
                    $investment = new Investment();
                    $investment->currency = $invoice->currency;
                    $investment->amount = $investorAmount;
                    // Fix: Calculate return properly using string conversion for rate
                    $rate = (string)($invoice->rate / 100); // Convert rate to decimal (e.g. 12% -> 0.12)
                    $investment->return = $investorAmount->multiply($rate);
                    $investment->rate = $invoice->rate;
                    $investment->due_date = $invoice->due_date;
                    $investment->status = 'active';
                    $investment->investor_id = $investor->id;
                    $investment->invoice_id = $invoice->id;

                    // Create movement record
                    $movement = new Movement();
                    $movement->currency = $invoice->currency;
                    $movement->amount = $investorAmount;
                    $movement->type = MovementType::INVESTMENT->value;
                    $movement->status = MovementStatus::VALID->value;
                    $movement->confirm_status = MovementStatus::VALID->value;
                    $movement->investor_id = $investor->id;
                    $movement->save();

                    // Update investment with movement_id
                    $investment->movement_id = $movement->id;
                    $investment->save();

                    // Update investor's balance
                    $balance->amount = $balance->amount->subtract($investorAmount);
                    $balance->invested_amount = isset($balance->invested_amount) ?
                        $balance->invested_amount->add($investorAmount) :
                        $investorAmount;
                    $balance->expected_amount = isset($balance->expected_amount) ?
                        $balance->expected_amount->add($investment->return) :
                        $investment->return;
                    $balance->save();

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                }
            }
        }
    }
}
