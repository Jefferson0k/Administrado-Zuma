<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->streetName,
            'distrito' => $this->faker->city,
            'descripcion' => $this->faker->paragraph,
            'validado' => $this->faker->boolean,
            'fecha_inversion' => $this->faker->optional()->date(),

            'valor_estimado' => $this->faker->randomFloat(2, 50000, 500000),
            'valor_subasta' => $this->faker->randomFloat(2, 30000, 400000),
            'currency_id' => 1,
            'deadlines_id' => 1,
            'tea' => $this->faker->randomFloat(2, 10, 25),
            'tem' => $this->faker->randomFloat(4, 0.5, 2.5),
            
            'estado' => 'no_subastada',
        ];
    }
}
