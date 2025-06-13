<?php 

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    public function definition()
    {
        return [
            'property_id' => Property::factory(),
            'monto_inicial' => 900,
            'dia_subasta' => $this->faker->date(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'tiempo_finalizacion' => now()->addHour(),
            'estado' => $this->faker->randomElement(['activa', 'finalizada']),
            'ganador_id' => null, // puedes llenar esto después
        ];
    }
}
