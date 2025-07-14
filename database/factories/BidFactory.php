<?php 

namespace Database\Factories;

use App\Models\Bid;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BidFactory extends Factory
{
    protected $model = Bid::class;

    public function definition()
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
            'monto' => $this->faker->numberBetween(900, 1200),
        ];
    }
}
