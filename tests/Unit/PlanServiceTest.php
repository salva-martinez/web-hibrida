<?php

namespace Tests\Unit;

use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\Feedback;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(User $paciente, Ejercicio $ejercicio, array $overrides = []): array
    {
        return array_merge([
            'paciente_id' => $paciente->id,
            'titulo' => 'Plan de prueba',
            'fecha' => now()->toDateString(),
            'activo' => true,
            'dias' => [
                1 => [
                    'notas_fisio' => null,
                    'ejercicios' => [
                        [
                            'ejercicio_id' => $ejercicio->id,
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

    public function test_crear_un_plan_desactiva_automaticamente_los_planes_activos_anteriores(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);

        $this->actingAs($fisio);

        $service = app(PlanService::class);

        $plan1 = $service->createPlan($this->payload($paciente, $ejercicio));
        $this->assertTrue($plan1->fresh()->activo);

        $plan2 = $service->createPlan($this->payload($paciente, $ejercicio, ['titulo' => 'Segundo ciclo']));

        $this->assertFalse($plan1->fresh()->activo, 'El plan anterior debe quedar inactivo.');
        $this->assertTrue($plan2->fresh()->activo);
    }

    public function test_actualizar_un_plan_no_destruye_el_feedback_de_los_dias_existentes(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);

        $this->actingAs($fisio);

        $plan = app(PlanService::class)->createPlan($this->payload($paciente, $ejercicio));

        $planDia = $plan->planDias()->first();
        $feedback = Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 8,
            'dolor' => 'Molestia ligera',
            'evolucion' => 'Bien',
            'comentario' => 'Duele un poco',
        ]);

        // Se actualiza el plan cambiando la carga.
        $payload = $this->payload($paciente, $ejercicio, ['titulo' => 'Plan ajustado']);
        $payload['dias'][1]['ejercicios'][0]['series'] = 4;

        app(PlanService::class)->updatePlan($plan, $payload);

        $this->assertDatabaseHas('feedback', ['id' => $feedback->id]);
        $this->assertDatabaseHas('plan_dias', ['id' => $planDia->id]);
        $this->assertSame(4, $plan->fresh()->planDias()->first()->planEjercicios()->first()->series);
    }

    public function test_el_plan_se_asigna_al_fisioterapeuta_autenticado(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);

        $this->actingAs($fisio);

        $plan = app(PlanService::class)->createPlan($this->payload($paciente, $ejercicio));

        $this->assertSame($fisio->id, $plan->fisio_id);
    }

    public function test_el_plan_queda_archivado_pero_conserva_sus_dias(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);

        $this->actingAs($fisio);

        $plan = app(PlanService::class)->createPlan($this->payload($paciente, $ejercicio));
        $planId = $plan->id;
        $planDiaId = $plan->planDias()->first()->id;

        $plan->delete();

        $this->assertSoftDeleted('planes', ['id' => $planId]);
        $this->assertDatabaseHas('plan_dias', ['id' => $planDiaId]);
    }
}
