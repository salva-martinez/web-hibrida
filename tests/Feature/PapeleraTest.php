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
 * Nada se borra físicamente: desactivar oculta, archivar conserva el historial
 * clínico y la papelera permite recuperar lo que se desactivó.
 */
class PapeleraTest extends TestCase
{
    use RefreshDatabase;

    private function crearFisio(): User
    {
        return User::factory()->fisio()->create();
    }

    public function test_desactivar_un_paciente_lo_oculta_pero_conserva_su_historial(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create();

        $plan = Plan::factory()->paraFisio($fisio)->create([
            'paciente_id' => $paciente->id,
        ]);

        $this->actingAs($fisio)
            ->delete(route('admin.pacientes.destroy', $paciente))
            ->assertRedirect(route('admin.pacientes.index'));

        $this->assertSoftDeleted('users', ['id' => $paciente->id]);

        // El plan sigue existiendo: no se destruye nada.
        $this->assertDatabaseHas('planes', ['id' => $plan->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $paciente->id]);
    }

    public function test_un_paciente_desactivado_no_aparece_en_la_lista_ni_puede_entrar(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create([
            'nombre' => 'Desactivado',
            'apellido1' => 'Paciente',
            'apellido2' => 'Prueba',
            'fecha_nacimiento' => '1990-07-22',
        ]);

        $this->actingAs($fisio)->delete(route('admin.pacientes.destroy', $paciente));

        $this->actingAs($fisio)
            ->get(route('admin.pacientes.index'))
            ->assertOk()
            ->assertDontSee('Desactivado Paciente Prueba');

        // Y tampoco puede iniciar sesión con sus datos de paciente.
        $this->post(route('logout'));
        $this->post(route('login'), [
            'login_type' => 'paciente',
            'nombre' => 'Desactivado',
            'apellido1' => 'Paciente',
            'apellido2' => 'Prueba',
            'fecha_nacimiento' => '1990-07-22',
        ])->assertSessionHasErrors('nombre');

        $this->assertGuest();
    }

    public function test_un_paciente_desactivado_se_puede_restaurar_desde_la_papelera(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create();

        $this->actingAs($fisio)->delete(route('admin.pacientes.destroy', $paciente));

        $this->actingAs($fisio)
            ->get(route('admin.papelera.index'))
            ->assertOk()
            ->assertSee($paciente->nombre_completo);

        $this->actingAs($fisio)
            ->post(route('admin.papelera.paciente.restore', $paciente->id))
            ->assertRedirect(route('admin.papelera.index'));

        $this->assertNotSoftDeleted('users', ['id' => $paciente->id]);
    }

    public function test_no_se_puede_restaurar_el_paciente_de_otro_fisioterapeuta(): void
    {
        $fisioA = $this->crearFisio();
        $fisioB = $this->crearFisio();
        $pacienteB = User::factory()->pacienteDe($fisioB)->create();

        $this->actingAs($fisioB)->delete(route('admin.pacientes.destroy', $pacienteB));

        $this->actingAs($fisioA)
            ->post(route('admin.papelera.paciente.restore', $pacienteB->id))
            ->assertNotFound();

        $this->assertSoftDeleted('users', ['id' => $pacienteB->id]);
    }

    public function test_archivar_un_plan_conserva_el_feedback_del_paciente(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);

        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        $feedback = Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 7,
            'dolor' => 'Sin dolor',
            'evolucion' => 'Normal',
            'comentario' => 'Todo bien',
        ]);

        $this->actingAs($fisio)
            ->delete(route('admin.planes.destroy', $plan))
            ->assertRedirect(route('admin.planes.index'));

        $this->assertSoftDeleted('planes', ['id' => $plan->id]);
        $this->assertDatabaseHas('feedback', ['id' => $feedback->id]);
        $this->assertDatabaseHas('plan_dias', ['id' => $planDia->id]);
    }

    public function test_un_plan_archivado_se_puede_restaurar(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);

        $this->actingAs($fisio)->delete(route('admin.planes.destroy', $plan));
        $this->assertSoftDeleted('planes', ['id' => $plan->id]);

        $this->actingAs($fisio)
            ->post(route('admin.papelera.plan.restore', $plan->id))
            ->assertRedirect(route('admin.papelera.index'));

        $this->assertNotSoftDeleted('planes', ['id' => $plan->id]);
    }

    public function test_al_restaurar_un_plan_activo_se_desactivan_los_otros_activos_del_paciente(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create();

        // El plan archivado estaba activo; al restaurarlo debe seguir siendo el
        // único plan activo del paciente.
        $planArchivado = Plan::factory()->paraFisio($fisio)->create([
            'paciente_id' => $paciente->id,
            'activo' => true,
        ]);
        $planActivo = Plan::factory()->paraFisio($fisio)->create([
            'paciente_id' => $paciente->id,
            'activo' => true,
        ]);

        $this->actingAs($fisio)->delete(route('admin.planes.destroy', $planArchivado));
        $this->actingAs($fisio)->post(route('admin.papelera.plan.restore', $planArchivado->id));

        $this->assertTrue($planArchivado->fresh()->activo);
        $this->assertFalse($planActivo->fresh()->activo);
    }

    public function test_al_restaurar_un_plan_inactivo_no_afecta_a_los_activos(): void
    {
        $fisio = $this->crearFisio();
        $paciente = User::factory()->pacienteDe($fisio)->create();

        $planArchivado = Plan::factory()->paraFisio($fisio)->create([
            'paciente_id' => $paciente->id,
            'activo' => false,
        ]);
        $planActivo = Plan::factory()->paraFisio($fisio)->create([
            'paciente_id' => $paciente->id,
            'activo' => true,
        ]);

        $this->actingAs($fisio)->delete(route('admin.planes.destroy', $planArchivado));
        $this->actingAs($fisio)->post(route('admin.papelera.plan.restore', $planArchivado->id));

        $this->assertFalse($planArchivado->fresh()->activo);
        $this->assertTrue($planActivo->fresh()->activo);
    }

    public function test_desactivar_un_estimulo_tambien_desactiva_sus_ejercicios(): void
    {
        $fisio = $this->crearFisio();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create([
            'estimulo_id' => $estimulo->id,
        ]);

        $this->actingAs($fisio)
            ->delete(route('admin.estimulos.destroy', $estimulo))
            ->assertRedirect(route('admin.estimulos.index'));

        $this->assertSoftDeleted('estimulos', ['id' => $estimulo->id]);
        $this->assertSoftDeleted('ejercicios', ['id' => $ejercicio->id]);
    }

    public function test_restaurar_un_estimulo_restaura_sus_ejercicios(): void
    {
        $fisio = $this->crearFisio();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create([
            'estimulo_id' => $estimulo->id,
        ]);

        $this->actingAs($fisio)->delete(route('admin.estimulos.destroy', $estimulo));
        $this->actingAs($fisio)->post(route('admin.papelera.estimulo.restore', $estimulo->id));

        $this->assertNotSoftDeleted('estimulos', ['id' => $estimulo->id]);
        $this->assertNotSoftDeleted('ejercicios', ['id' => $ejercicio->id]);
    }
}
