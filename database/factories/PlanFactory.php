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
            'fisio_id' => User::factory()->fisio(),
            'titulo' => $this->faker->sentence(),
            'fecha' => now(),
            'activo' => true,
        ];
    }

    /**
     * Plan de un paciente propiedad del fisioterapeuta indicado.
     */
    public function paraFisio(User $fisio): static
    {
        return $this->state(fn (array $attributes) => [
            'fisio_id' => $fisio->id,
            'paciente_id' => User::factory()->pacienteDe($fisio),
        ]);
    }

    /**
     * Plan archivado (borrado lógico).
     */
    public function archivado(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
