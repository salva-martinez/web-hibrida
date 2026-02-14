<?php

namespace Database\Factories;

use App\Models\Estimulo;
use Illuminate\Database\Eloquent\Factories\Factory;

class EjercicioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estimulo_id' => Estimulo::factory(),
            'nombre' => $this->faker->word(),
            'video_url' => $this->faker->url(),
        ];
    }
}
