<?php

namespace Database\Factories;

use App\Models\Estimulo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstimuloFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word(),
            'descripcion' => $this->faker->sentence(),
            'orden' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Estímulo propiedad del fisioterapeuta indicado.
     */
    public function deFisio(User $fisio): static
    {
        return $this->state(fn (array $attributes) => [
            'fisio_id' => $fisio->id,
        ]);
    }
}
