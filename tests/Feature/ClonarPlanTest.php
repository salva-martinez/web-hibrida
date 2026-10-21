<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\Plan;
use App\Models\PlanDia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El duplicador de planes es la funcionalidad estrella: al clonar hay que
 * conservar TODOS los días del plan de origen, no solo el primero.
 */
class ClonarPlanTest extends TestCase
{
    use RefreshDatabase;

    private function crearPlanMultidia(User $fisio, User $paciente, int $numDias): Plan
    {
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);

        for ($i = 1; $i <= $numDias; $i++) {
            $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => $i]);
            $planDia->planEjercicios()->create([
                'ejercicio_id' => $ejercicio->id,
                'series' => 3,
                'repeticiones' => '10',
                'intensidad' => 6,
                'orden' => 0,
            ]);
        }

        return $plan;
    }

    public function test_la_vista_de_duplicar_preselecciona_todos_los_dias_del_plan_de_origen(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $plan = $this->crearPlanMultidia($fisio, $paciente, 3);

        $response = $this->actingAs($fisio)
            ->get(route('admin.planes.create', ['clone_id' => $plan->id]));

        $response->assertOk();

        // El <select> de días debe venir con "3 días" preseleccionado: si marca
        // 1, el JS solo crea el DOM del día 1 y el resto se pierde al guardar.
        $response->assertSee('<option value="3" selected>', false);
        $response->assertSee('<option value="1" >', false);
    }

    public function test_la_vista_de_editar_preselecciona_el_numero_de_dias_del_plan(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $plan = $this->crearPlanMultidia($fisio, $paciente, 4);

        $this->actingAs($fisio)
            ->get(route('admin.planes.edit', $plan))
            ->assertOk()
            ->assertSee('<option value="4" selected>', false);
    }

    public function test_no_se_puede_duplicar_el_plan_de_otro_fisioterapeuta(): void
    {
        $fisioA = User::factory()->fisio()->create();
        $fisioB = User::factory()->fisio()->create();
        $pacienteB = User::factory()->pacienteDe($fisioB)->create();
        $planB = $this->crearPlanMultidia($fisioB, $pacienteB, 2);

        $this->actingAs($fisioA)
            ->get(route('admin.planes.create', ['clone_id' => $planB->id]))
            ->assertNotFound();
    }

    public function test_el_plan_duplicado_se_asigna_al_fisio_que_duplica(): void
    {
        $fisioA = User::factory()->fisio()->create();
        $fisioB = User::factory()->fisio()->create();
        $pacienteA = User::factory()->pacienteDe($fisioA)->create();
        $pacienteB = User::factory()->pacienteDe($fisioB)->create();

        $estimulo = Estimulo::factory()->deFisio($fisioA)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisioA)->create(['estimulo_id' => $estimulo->id]);

        $planOrigen = Plan::factory()->paraFisio($fisioA)->create(['paciente_id' => $pacienteA->id]);
        $planDia = PlanDia::create(['plan_id' => $planOrigen->id, 'numero_dia' => 1]);
        $planDia->planEjercicios()->create([
            'ejercicio_id' => $ejercicio->id,
            'series' => 3,
            'repeticiones' => '10',
            'intensidad' => 6,
            'orden' => 0,
        ]);

        $this->actingAs($fisioB)->post(route('admin.planes.store'), [
            'paciente_id' => $pacienteB->id,
            'titulo' => $planOrigen->titulo.' (Copia)',
            'fecha' => now()->toDateString(),
            'dias' => [
                1 => [
                    'ejercicios' => [
                        ['ejercicio_id' => $ejercicio->id, 'series' => 3, 'repeticiones' => '10', 'intensidad' => 6],
                    ],
                ],
            ],
        ])->assertRedirect(route('admin.planes.index'));

        $this->assertDatabaseHas('planes', [
            'titulo' => $planOrigen->titulo.' (Copia)',
            'fisio_id' => $fisioB->id,
            'paciente_id' => $pacienteB->id,
        ]);
    }
}
