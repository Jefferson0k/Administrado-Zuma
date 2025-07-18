<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\MoneyConverter;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companiesIDs = DB::table('companies')->pluck('id');
        $currencies = Currency::cases();
        $statuses = [
            InvoiceStatus::ACTIVE->value,
            InvoiceStatus::INACTIVE->value,
            // InvoiceStatus::PAID->value,
            InvoiceStatus::REPROGRAMED->value,
            InvoiceStatus::EXPIRED->value,
            InvoiceStatus::JUDICIALIZED->value,
        ];

        $faker = Factory::create();
        $faker_locale = Factory::create("es_PE");

        for ($i = 0; $i < 40; $i++) {
            $currency = $faker->randomElement($currencies)->value;
            $amount = MoneyConverter::fromDecimal($faker_locale->randomFloat(2, 15000, 50000), $currency);
            // El monto financiado será un porcentaje aleatorio entre 60% y 95% del monto total
            $randomPercentage = $faker->randomFloat(2, 0.1, 0.3);
            $financedAmount = $amount->multiply((string) $randomPercentage);
            $financedAmountByGarantia = $amount->multiply('0.2'); // 20% of the amount

            // Calculate due date and estimated pay date
            $dueDate = $faker_locale->dateTimeInInterval('now', '+100 days');
            $estimatedPayDate = $faker_locale->dateTimeInInterval('now', '+90 days');

            // Create the invoice
            Invoice::create([
                'invoice_code' => Str::ulid(),
                'currency' => $currency,
                'amount' => $amount,
                'financed_amount_by_garantia' => $financedAmountByGarantia,
                'financed_amount' => $financedAmount,
                'paid_amount' => MoneyConverter::fromDecimal(0, $currency),
                'rate' => $faker_locale->randomFloat(2, 1, 10),
                'due_date' => $dueDate,
                'estimated_pay_date' => $estimatedPayDate,
                'status' => $faker->randomElement(array_merge(
                    array_fill(0, 5, InvoiceStatus::ACTIVE->value), // 8x weight for ACTIVE
                    $statuses
                )),
                'company_id' => $faker->randomElement($companiesIDs),
                'loan_number' => $faker_locale->unique()->numerify('LOAN-' . date('Y') . '-#######'),
                'RUC_client' => $faker_locale->numerify('###########'), // 11 digits RUC
                'invoice_number' => $faker_locale->unique()->numerify('F' . date('Y') . '-##########'),
                'created_by' => 1, // Assuming admin user with ID 1
                'updated_by' => 1,
            ]);
        }
    }
}
