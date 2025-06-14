<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class invoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid(),
            'invoice_code' => Str::uuid(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'financed_amount_by_garantia' => $this->faker->randomFloat(2, 50, 5000),
            'financed_amount' => $this->faker->randomFloat(2, 100, 10000),
            'paid_amount' => $this->faker->randomFloat(2, 0, 5000),
            'rate' => $this->faker->randomFloat(2, 1, 10),
            'due_date' => $this->faker->dateTimeBetween('+30 days', '+120 days')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'company_id' => null,
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
