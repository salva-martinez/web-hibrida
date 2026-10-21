<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada fisioterapeuta es una isla: no debe poder ver ni tocar los datos de otro.
 */
class AislamientoFisiosTest extends TestCase
{
    use RefreshDatabase;

    private function crearFisio(string $nombre): User
    {
        return User::factory()->fisio()->create([
            'nombre' => $nombre,
            'apellido1' => 'Prueba',
            'apellido2' => 'Fisio',
        ]);
    }

    private function crearPaciente(User $fisio, string $nombre): User
    {
        return User::factory()->pacienteDe($fisio)->create([
            'nombre' => $nombre,
            'apellido1' => 'Paciente',
            'apellido2' => 'Prueba',
        ]);
    }

    public function test_la_lista_de_pacientes_solo_muestra_la_cartera_propia(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');

        $pacienteA = $this->crearPaciente($fisioA, 'Propio');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        $this->actingAs($fisioA)
            ->get(route('admin.pacientes.index'))
            ->assertOk()
            ->assertSee('Propio Paciente Prueba')
            ->assertDontSee('Ajeno Paciente Prueba');
    }

    public function test_no_se_puede_ver_la_ficha_de_un_paciente_ajeno(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        // El proyecto convierte el 403 en un redirect con mensaje (ver bootstrap/app.php).
        $response = $this->actingAs($fisioA)
            ->get(route('admin.pacientes.show', $pacienteB));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error');
        $response->assertDontSee($pacienteB->nombre_completo);
    }

    public function test_no_se_puede_editar_un_paciente_ajeno(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        $this->actingAs($fisioA)
            ->put(route('admin.pacientes.update', $pacienteB), [
                'nombre' => 'Hackeado',
                'apellido1' => 'X',
                'apellido2' => 'Y',
                'fecha_nacimiento' => '1990-01-01',
            ])
            ->assertRedirect(route('admin.dashboard'));

        // La garantía real: los datos del paciente ajeno no cambian.
        $this->assertDatabaseHas('users', [
            'id' => $pacienteB->id,
            'nombre' => 'Ajeno',
        ]);
    }

    public function test_no_se_puede_desactivar_un_paciente_ajeno(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        $this->actingAs($fisioA)
            ->delete(route('admin.pacientes.destroy', $pacienteB))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $pacienteB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_no_se_puede_desactivar_la_cuenta_de_otro_fisioterapeuta(): void
    {
        // Regresión: antes, DELETE /admin/pacientes/{id} borraba físicamente
        // cualquier usuario, incluidos los fisioterapeutas.
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');

        $this->actingAs($fisioA)
            ->delete(route('admin.pacientes.destroy', $fisioB))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $fisioB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_no_se_puede_consultar_el_plan_de_otro_fisioterapeuta(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');
        $planB = Plan::factory()->paraFisio($fisioB)->create([
            'paciente_id' => $pacienteB->id,
        ]);

        $this->actingAs($fisioA)
            ->get(route('admin.planes.show', $planB))
            ->assertNotFound();

        $this->actingAs($fisioA)
            ->get(route('admin.planes.edit', $planB))
            ->assertNotFound();
    }

    public function test_la_lista_de_planes_no_incluye_planes_de_otros(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');

        $pacienteA = $this->crearPaciente($fisioA, 'Propio');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        Plan::factory()->paraFisio($fisioA)->create([
            'paciente_id' => $pacienteA->id,
            'titulo' => 'Plan propio',
        ]);
        Plan::factory()->paraFisio($fisioB)->create([
            'paciente_id' => $pacienteB->id,
            'titulo' => 'Plan ajeno',
        ]);

        $this->actingAs($fisioA)
            ->get(route('admin.planes.index'))
            ->assertOk()
            ->assertSee('Plan propio')
            ->assertDontSee('Plan ajeno');
    }

    public function test_el_chat_no_acepta_el_historial_de_un_paciente_ajeno(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        $this->actingAs($fisioA)
            ->postJson(route('admin.chat.send'), [
                'message' => '¿Cómo evoluciona?',
                'patient_id' => $pacienteB->id,
            ])
            ->assertNotFound();
    }

    public function test_el_catalogo_de_ejercicios_es_propio(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');

        Ejercicio::factory()->deFisio($fisioA)->create(['nombre' => 'Sentadilla propia']);
        Ejercicio::factory()->deFisio($fisioB)->create(['nombre' => 'Sentadilla ajena']);

        $this->actingAs($fisioA)
            ->get(route('admin.ejercicios.index'))
            ->assertOk()
            ->assertSee('Sentadilla propia')
            ->assertDontSee('Sentadilla ajena');
    }

    public function test_un_paciente_no_puede_ser_asignado_a_un_otro_fisioterapeuta(): void
    {
        $fisioA = $this->crearFisio('Fisio');
        $fisioB = $this->crearFisio('Colega');
        $pacienteB = $this->crearPaciente($fisioB, 'Ajeno');

        $this->actingAs($fisioA)
            ->post(route('admin.planes.store'), [
                'paciente_id' => $pacienteB->id,
                'titulo' => 'Plan intruso',
                'fecha' => now()->toDateString(),
                'dias' => [
                    1 => [
                        'ejercicios' => [
                            ['ejercicio_id' => Ejercicio::factory()->deFisio($fisioA)->create()->id,
                                'series' => 3, 'repeticiones' => '10', 'intensidad' => 5],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors('paciente_id');

        $this->assertDatabaseMissing('planes', ['titulo' => 'Plan intruso']);
    }
}
