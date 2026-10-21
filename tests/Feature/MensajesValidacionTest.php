<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los errores de validación deben verse junto al campo que falló, en español.
 *
 * Sin el fichero lang/es/validation.php, Laravel devuelve la clave de la regla
 * ("validation.required") en lugar de un mensaje legible, porque el framework
 * solo trae las traducciones en inglés.
 */
class MensajesValidacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_traducciones_en_espanol_existen(): void
    {
        $this->assertFileExists(lang_path('es/validation.php'));
    }

    public function test_el_mensaje_de_required_no_devuelve_la_clave(): void
    {
        $mensaje = trans('validation.required', ['attribute' => 'paciente']);

        $this->assertNotSame('validation.required', $mensaje);
        $this->assertStringContainsString('paciente', $mensaje);
        $this->assertStringContainsString('obligatorio', $mensaje);
    }

    public function test_los_atributos_se_traducen_a_espanol(): void
    {
        $mensaje = trans('validation.required', ['attribute' => 'paciente_id']);

        // Con el atributo personalizado, el mensaje dice "paciente" y no
        // "paciente id".
        $this->assertStringContainsString('paciente', $mensaje);
        $this->assertStringNotContainsString('paciente id', $mensaje);
    }

    /**
     * Devuelve [mensaje de error en sesión, HTML del formulario renderizado].
     */
    private function enviarPlan(array $payload, User $fisio): array
    {
        $url = route('admin.planes.store');
        $origen = route('admin.planes.create');

        // Primera petición: se comprueba la sesión de errores y se guarda el
        // mensaje (la segunda petición consume la sesión).
        // "from" simula el Referer del navegador, que es lo que hace que Laravel
        // vuelva al formulario tras un error.
        $this->actingAs($fisio)
            ->from($origen)
            ->post($url, $payload)
            ->assertSessionHasErrors('paciente_id');

        $mensaje = session('errors')->getBag('default')->get('paciente_id')[0] ?? '';

        // Segunda petición: se sigue la redirección para ver el formulario.
        $html = $this->actingAs($fisio)
            ->from($origen)
            ->followingRedirects()
            ->post($url, $payload)
            ->getContent();

        return [$mensaje, $html];
    }

    public function test_el_error_del_paciente_aparece_junto_al_campo(): void
    {
        $fisio = User::factory()->fisio()->create();
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);

        // Sin paciente: el campo falla y el mensaje sale bajo el select.
        [$mensaje, $html] = $this->enviarPlan([
            'titulo' => 'Plan sin paciente',
            'fecha' => now()->toDateString(),
            'dias' => [
                1 => [
                    'ejercicios' => [
                        ['ejercicio_id' => $ejercicio->id, 'series' => 3, 'repeticiones' => '10', 'intensidad' => 5],
                    ],
                ],
            ],
        ], $fisio);

        // El mensaje es legible y en español.
        $this->assertNotSame('validation.required', $mensaje);
        $this->assertStringContainsString('paciente', $mensaje);
        $this->assertStringContainsString('obligatorio', $mensaje);

        // Y aparece junto al campo, no solo en el bloque de arriba.
        $this->assertStringContainsString('field-error', $html);
        $this->assertStringNotContainsString('validation.required', $html);
    }

    public function test_el_mensaje_de_paciente_ajeno_es_explicito(): void
    {
        $fisioA = User::factory()->fisio()->create();
        $fisioB = User::factory()->fisio()->create();
        $pacienteB = User::factory()->pacienteDe($fisioB)->create();
        $estimulo = Estimulo::factory()->deFisio($fisioA)->create();
        $ejercicio = Ejercicio::factory()->deFisio($fisioA)->create(['estimulo_id' => $estimulo->id]);

        [$mensaje] = $this->enviarPlan([
            'paciente_id' => $pacienteB->id,
            'titulo' => 'Plan intruso',
            'fecha' => now()->toDateString(),
            'dias' => [
                1 => [
                    'ejercicios' => [
                        ['ejercicio_id' => $ejercicio->id, 'series' => 3, 'repeticiones' => '10', 'intensidad' => 5],
                    ],
                ],
            ],
        ], $fisioA);

        $this->assertStringContainsString('tu cartera', $mensaje);
    }

    public function test_el_error_de_un_ejercicio_usa_el_nombre_del_campo(): void
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();

        $url = route('admin.planes.store');

        $this->actingAs($fisio)->post($url, [
            'paciente_id' => $paciente->id,
            'titulo' => 'Plan con intensidad inválida',
            'fecha' => now()->toDateString(),
            'dias' => [
                1 => [
                    'ejercicios' => [
                        ['ejercicio_id' => 1, 'series' => 3, 'repeticiones' => '10', 'intensidad' => 99],
                    ],
                ],
            ],
        ])->assertSessionHasErrors('dias.1.ejercicios.0.intensidad');

        $sesion = session('errors')->getBag('default')->get('dias.1.ejercicios.0.intensidad')[0] ?? '';

        $this->assertStringNotContainsString('validation.', $sesion);
        $this->assertStringContainsString('intensidad', $sesion);
    }
}
