<?php 

namespace Database\Factories;

use App\Models\Investment;
use App\Models\User;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'property_id' => Property::factory(),
            'monto_invertido' => $this->faker->numberBetween(1000, 5000),
            'fecha_inversion' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
