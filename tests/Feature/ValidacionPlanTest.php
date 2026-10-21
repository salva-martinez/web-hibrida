<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Crear y editar un plan comparten el mismo contrato de validación.
 *
 * Antes las dos rutas tenían reglas distintas: el mismo plan era válido al
 * editarlo e inválido al crearlo (repeticiones con rango, intensidad 0, kg 0).
 */
class ValidacionPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $fisio;

    private User $paciente;

    private Ejercicio $ejercicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fisio = User::factory()->fisio()->create();
        $this->paciente = User::factory()->pacienteDe($this->fisio)->create();
        $estimulo = Estimulo::factory()->deFisio($this->fisio)->create();
        $this->ejercicio = Ejercicio::factory()->deFisio($this->fisio)->create(['estimulo_id' => $estimulo->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'paciente_id' => $this->paciente->id,
            'titulo' => 'Plan de prueba',
            'fecha' => now()->toDateString(),
            'dias' => [
                1 => [
                    'notas_fisio' => null,
                    'ejercicios' => [
                        [
                            'ejercicio_id' => $this->ejercicio->id,
                            'series' => 3,
                            'repeticiones' => '10',
                            'intensidad' => 6,
                            'kg' => 20,
                            'descanso' => '2 min',
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    public function test_se_acepta_un_rango_de_repeticiones_al_crear(): void
    {
        $payload = $this->payload();
        $payload['dias'][1]['ejercicios'][0]['repeticiones'] = '8-10';

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('planes', ['titulo' => 'Plan de prueba']);
    }

    public function test_se_acepta_una_intensidad_de_cero_al_crear(): void
    {
        $payload = $this->payload();
        $payload['dias'][1]['ejercicios'][0]['intensidad'] = 0;

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $payload)
            ->assertSessionHasNoErrors();
    }

    public function test_se_acepta_un_peso_de_cero_al_crear(): void
    {
        $payload = $this->payload();
        $payload['dias'][1]['ejercicios'][0]['kg'] = 0;

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $payload)
            ->assertSessionHasNoErrors();
    }

    public function test_las_mismas_reglas_se_aplican_al_editar(): void
    {
        $plan = Plan::factory()->paraFisio($this->fisio)->create([
            'paciente_id' => $this->paciente->id,
        ]);

        $payload = $this->payload();
        $payload['dias'][1]['ejercicios'][0]['repeticiones'] = '8-10';
        $payload['dias'][1]['ejercicios'][0]['intensidad'] = 0;
        $payload['dias'][1]['ejercicios'][0]['kg'] = 0;

        $this->actingAs($this->fisio)
            ->put(route('admin.planes.update', $plan), $payload)
            ->assertSessionHasNoErrors();
    }

    public function test_se_rechaza_una_intensidad_por_encima_de_diez(): void
    {
        $payload = $this->payload();
        $payload['dias'][1]['ejercicios'][0]['intensidad'] = 11;

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $payload)
            ->assertSessionHasErrors('dias.1.ejercicios.0.intensidad');
    }

    public function test_se_rechaza_un_descanso_muy_largo(): void
    {
        $payload = $this->payload();
        $payload['dias'][1]['ejercicios'][0]['descanso'] = str_repeat('x', 40);

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $payload)
            ->assertSessionHasErrors('dias.1.ejercicios.0.descanso');
    }

    public function test_no_se_admite_un_paciente_inexistente(): void
    {
        $payload = $this->payload(['paciente_id' => 999999]);

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $payload)
            ->assertSessionHasErrors('paciente_id');
    }

    public function test_no_se_admite_un_fisio_como_paciente(): void
    {
        $otroFisio = User::factory()->fisio()->create();

        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $this->payload(['paciente_id' => $otroFisio->id]))
            ->assertSessionHasErrors('paciente_id');
    }
}
