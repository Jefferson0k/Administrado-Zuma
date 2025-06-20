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
            'foto' => 'imagen.jpeg',
            'validado' => $this->faker->boolean,
            'fecha_inversion' => $this->faker->optional()->date(),
            'estado' => 'no_subastada',
        ];
    }
}
