<?php

namespace Database\Factories;

use App\Models\Estimulo;
use App\Models\User;
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

    /**
     * Ejercicio propiedad del fisioterapeuta indicado (con su estímulo incluido).
     */
    public function deFisio(User $fisio): static
    {
        return $this->state(fn (array $attributes) => [
            'fisio_id' => $fisio->id,
            'estimulo_id' => Estimulo::factory()->deFisio($fisio),
        ]);
    }
}
