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
 * El editor de planes no debe perder el trabajo cuando la validación falla.
 *
 * Antes, un 422 devolvía el formulario vacío y el usuario tenía que volver a
 * escribir el plan entero. Ahora se repone lo que ya había escrito.
 */
class EditorPlanesTest extends TestCase
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
            'titulo' => 'MI TITULO ESCRITO',
            'fecha' => now()->toDateString(),
            'dias' => [
                1 => [
                    'notas_fisio' => 'MI NOTA PARA EL PACIENTE',
                    'ejercicios' => [
                        [
                            'ejercicio_id' => $this->ejercicio->id,
                            'series' => 4,
                            'repeticiones' => '8-10',
                            'intensidad' => 11, // inválido: dispara el 422
                            'kg' => null,
                            'descanso' => null,
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    /**
     * Los datos del editor viajan en un atributo data- de la vista.
     */
    private function datosDelEditor(string $html): array
    {
        preg_match('/data-datos="([^"]*)"/', $html, $coincidencias);

        return json_decode(html_entity_decode($coincidencias[1] ?? '{}'), true) ?: [];
    }

    public function test_si_la_validacion_falla_se_reponen_los_datos_escritos(): void
    {
        $this->actingAs($this->fisio)
            ->post(route('admin.planes.store'), $this->payload([]))
            ->assertSessionHasErrors('dias.1.ejercicios.0.intensidad');

        // Al volver al formulario, todo lo escrito debe estar ahí.
        $respuesta = $this->actingAs($this->fisio)->get(route('admin.planes.create'));

        $respuesta->assertOk()
            ->assertSee('MI TITULO ESCRITO')
            ->assertSee('MI NOTA PARA EL PACIENTE');

        // Y los datos de los ejercicios llegan al módulo JS.
        $datos = $this->datosDelEditor($respuesta->getContent());
        $ejercicio = $datos['dias'][1]['plan_ejercicios'][0] ?? [];

        $this->assertSame('8-10', $ejercicio['repeticiones'] ?? null);
        $this->assertSame(4, (int) ($ejercicio['series'] ?? 0));
    }

    public function test_al_reabrir_el_editor_los_dias_vienen_del_plan(): void
    {
        $plan = Plan::factory()->paraFisio($this->fisio)->create(['paciente_id' => $this->paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1, 'notas_fisio' => 'NOTAS DEL PLAN']);
        $planDia->planEjercicios()->create([
            'ejercicio_id' => $this->ejercicio->id,
            'series' => 3,
            'repeticiones' => '10',
            'intensidad' => 6,
            'orden' => 0,
        ]);

        $respuesta = $this->actingAs($this->fisio)->get(route('admin.planes.edit', $plan));

        $respuesta->assertOk()->assertSee('NOTAS DEL PLAN');

        $datos = $this->datosDelEditor($respuesta->getContent());

        $this->assertSame('10', $datos['dias'][1]['plan_ejercicios'][0]['repeticiones'] ?? null);
        $this->assertSame('NOTAS DEL PLAN', $datos['dias'][1]['notas_fisio'] ?? null);
    }

    public function test_avisa_si_el_plan_tiene_dias_con_feedback_registrado(): void
    {
        $plan = Plan::factory()->paraFisio($this->fisio)->create(['paciente_id' => $this->paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);
        Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 6,
            'dolor' => 'Sin dolor',
            'evolucion' => 'Bien',
        ]);

        $respuesta = $this->actingAs($this->fisio)->get(route('admin.planes.edit', $plan));

        $respuesta->assertOk()->assertSee('feedback registrado', false);

        // El módulo JS necesita saberlo para pedir confirmación al reducir.
        $datos = $this->datosDelEditor($respuesta->getContent());

        $this->assertSame(1, $datos['diasConFeedback'] ?? null);
    }

    public function test_un_plan_sin_feedback_no_muestra_el_aviso(): void
    {
        $plan = Plan::factory()->paraFisio($this->fisio)->create(['paciente_id' => $this->paciente->id]);
        PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);

        $respuesta = $this->actingAs($this->fisio)->get(route('admin.planes.edit', $plan));

        $respuesta->assertOk()->assertDontSee('feedback registrado', false);

        $datos = $this->datosDelEditor($respuesta->getContent());

        $this->assertSame(0, $datos['diasConFeedback'] ?? null);
    }
}
