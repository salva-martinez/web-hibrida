<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\Feedback;
use App\Models\Plan;
use App\Models\PlanDia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresiones de relaciones: un `withTrashed()` sobre una relación cuyo modelo no
 * usa SoftDeletes rompe el eager loading (Laravel lo reporta como "relación
 * inexistente"). Estos tests cubren cada relación del panel.
 */
class RelacionesEagerLoadingTest extends TestCase
{
    use RefreshDatabase;

    private function crearCatalogo(User $fisio): Ejercicio
    {
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();

        return Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);
    }

    public function test_el_dashboard_del_fisio_carga_los_ultimos_feedbacks(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $ejercicio = $this->crearCatalogo($fisio);

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 6,
            'dolor' => 'Sin dolor',
            'evolucion' => 'Normal',
            'comentario' => 'Sesión correcta',
        ]);

        $this->actingAs($fisio)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($paciente->nombre_completo);
    }

    public function test_la_ficha_del_plan_carga_dias_ejercicios_y_feedback(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $ejercicio = $this->crearCatalogo($fisio);

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        $planDia->planEjercicios()->create([
            'ejercicio_id' => $ejercicio->id,
            'series' => 3,
            'repeticiones' => '10',
            'intensidad' => 6,
            'orden' => 0,
        ]);
        Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 6,
            'dolor' => 'Sin dolor',
            'evolucion' => 'Normal',
        ]);

        $this->actingAs($fisio)
            ->get(route('admin.planes.show', $plan))
            ->assertOk()
            ->assertSee($ejercicio->nombre);
    }

    public function test_la_ficha_del_paciente_carga_sus_planes_con_feedback(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $ejercicio = $this->crearCatalogo($fisio);

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        $planDia->planEjercicios()->create([
            'ejercicio_id' => $ejercicio->id,
            'series' => 3,
            'repeticiones' => '10',
            'intensidad' => 6,
            'orden' => 0,
        ]);

        $this->actingAs($fisio)
            ->get(route('admin.pacientes.show', $paciente))
            ->assertOk()
            ->assertSee($plan->titulo);
    }

    public function test_la_papelera_carga_pacientes_y_planes_archivados(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);

        $this->actingAs($fisio)->delete(route('admin.pacientes.destroy', $paciente));
        $this->actingAs($fisio)->delete(route('admin.planes.destroy', $plan));

        $this->actingAs($fisio)
            ->get(route('admin.papelera.index'))
            ->assertOk()
            ->assertSee($paciente->nombre_completo)
            ->assertSee($plan->titulo);
    }

    public function test_el_paciente_ve_su_plan_con_ejercicios_y_estimulos(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $ejercicio = $this->crearCatalogo($fisio);

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        $planDia->planEjercicios()->create([
            'ejercicio_id' => $ejercicio->id,
            'series' => 3,
            'repeticiones' => '10',
            'intensidad' => 6,
            'orden' => 0,
        ]);

        $this->actingAs($paciente)
            ->get(route('paciente.plan.show', $plan))
            ->assertOk()
            ->assertSee($ejercicio->nombre)
            ->assertSee($ejercicio->estimulo->nombre);
    }

    public function test_un_ejercicio_desactivado_sigue_v_isibl_e_en_el_plan_where_fue_pautado(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $ejercicio = $this->crearCatalogo($fisio);

        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        $planDia->planEjercicios()->create([
            'ejercicio_id' => $ejercicio->id,
            'series' => 3,
            'repeticiones' => '10',
            'intensidad' => 6,
            'orden' => 0,
        ]);

        // Se desactiva el ejercicio: desaparece del catálogo pero no del histórico.
        $ejercicio->delete();

        $this->actingAs($paciente)
            ->get(route('paciente.plan.show', $plan))
            ->assertOk()
            ->assertSee($ejercicio->nombre);
    }
}
