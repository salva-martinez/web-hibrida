<?php

namespace Database\Factories;

use App\Models\User;
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
            'nombre' => fake()->firstName(),
            'apellido1' => fake()->lastName(),
            'apellido2' => fake()->lastName(),
            'fecha_nacimiento' => fake()->date(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'paciente',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Un fisioterapeuta: cada uno es su propia isla, sin propietario.
     */
    public function fisio(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'fisio',
            'fisio_id' => null,
        ]);
    }

    /**
     * Un paciente propiedad del fisioterapeuta indicado.
     */
    public function pacienteDe(User $fisio): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'paciente',
            'fisio_id' => $fisio->id,
        ]);
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
