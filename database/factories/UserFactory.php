<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName(),
            'dni' => $this->faker->unique()->numerify('########'),
            'nacimiento' => $this->faker->date('Y-m-d', '-18 years'), // al menos mayor de edad
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'status' => 1,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'monto' => $this->faker->randomFloat(2, 100, 10000),
            'restablecimiento' => $this->faker->numberBetween(1000, 9999),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
