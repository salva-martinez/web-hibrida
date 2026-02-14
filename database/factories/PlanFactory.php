<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'paciente_id' => User::factory(),
            'fisio_id' => User::factory()->state(['role' => 'fisio']),
            'titulo' => $this->faker->sentence(),
            'fecha' => now(),
            'activo' => true,
        ];
    }
}
