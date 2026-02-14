<?php

namespace Database\Factories;

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
}
