<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Plan;
use App\Models\PlanDia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El análisis de IA se genera en segundo plano: el panel del fisioterapeuta
 * consulta su estado en lugar de obligarle a recargar la página.
 */
class AnaliasIaStatusTest extends TestCase
{
    use RefreshDatabase;

    private function crearFeedback(User $fisio, bool $conAnalisis = false): Feedback
    {
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);

        return Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 6,
            'dolor' => 'Sin dolor',
            'evolucion' => 'Bien',
            'comentario' => 'Sesión correcta',
            'analisis_ia' => $conAnalisis ? 'Informe ya generado.' : null,
        ]);
    }

    public function test_devuelve_pendiente_cuando_el_informe_no_esta_listo(): void
    {
        $fisio = User::factory()->fisio()->create();
        $feedback = $this->crearFeedback($fisio);

        $this->actingAs($fisio)
            ->getJson(route('admin.feedback.analisis', $feedback))
            ->assertOk()
            ->assertJson(['estado' => 'pendiente', 'analisis' => null]);
    }

    public function test_devuelve_listo_cuando_el_informe_ya_existe(): void
    {
        $fisio = User::factory()->fisio()->create();
        $feedback = $this->crearFeedback($fisio, conAnalisis: true);

        $this->actingAs($fisio)
            ->getJson(route('admin.feedback.analisis', $feedback))
            ->assertOk()
            ->assertJson(['estado' => 'listo', 'analisis' => 'Informe ya generado.']);
    }

    public function test_no_se_puede_consultar_el_analisis_de_otro_fisioterapeuta(): void
    {
        $fisioA = User::factory()->fisio()->create();
        $fisioB = User::factory()->fisio()->create();
        $feedback = $this->crearFeedback($fisioB);

        $this->actingAs($fisioA)
            ->getJson(route('admin.feedback.analisis', $feedback))
            ->assertForbidden();
    }

    public function test_la_ficha_del_plan_marca_el_analisis_como_pendiente(): void
    {
        $fisio = User::factory()->fisio()->create();
        $feedback = $this->crearFeedback($fisio);
        $plan = $feedback->planDia->plan;

        $this->actingAs($fisio)
            ->get(route('admin.planes.show', $plan))
            ->assertOk()
            ->assertSee('data-estado="pendiente"', false)
            ->assertSee(route('admin.feedback.analisis', $feedback), false);
    }

    public function test_la_ficha_del_paciente_marca_el_analisis_como_pendiente(): void
    {
        $fisio = User::factory()->fisio()->create();
        $feedback = $this->crearFeedback($fisio);
        $paciente = $feedback->planDia->plan->paciente;

        $this->actingAs($fisio)
            ->get(route('admin.pacientes.show', $paciente))
            ->assertOk()
            ->assertSee('data-estado="pendiente"', false);
    }

    public function test_el_modulo_de_polling_se_sirve_en_el_bundle(): void
    {
        // El módulo que consulta el estado debe estar en resources/js y
        // llegar al navegador a través del bundle de Vite.
        $this->assertFileExists(resource_path('js/ai-status.js'));
        $this->assertStringContainsString(
            "import './ai-status'",
            file_get_contents(resource_path('js/app.js'))
        );
    }
}
