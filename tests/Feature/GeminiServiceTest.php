<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeFeedbackJob;
use App\Models\Feedback;
use App\Models\Plan;
use App\Models\PlanDia;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * La IA de Google es poco predecible: retira modelos, se satura y agota la
 * cuota del plan gratuito. El servicio tiene que sobrevivir a eso: probar
 * varios modelos, clasificar el fallo y explicar al fisioterapeuta qué pasa
 * sin soltarle un código HTTP.
 */
class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.api_key' => 'clave-de-prueba']);
    }

    /**
     * Pinta una respuesta correcta de la API.
     */
    private function respuestaOk(string $texto = 'Informe clínico')
    {
        return Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => $texto]]]],
            ],
        ], 200);
    }

    public function test_devuelve_el_texto_cuando_el_modelo_responde(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => $this->respuestaOk('Carga adecuada, mantener.'),
        ]);

        $servicio = new GeminiService;

        $this->assertSame(
            'Carga adecuada, mantener.',
            $servicio->analyzeFeedback(6, 'Cervical', 'Mejor', null)
        );
    }

    public function test_prueba_el_siguiente_modelo_cuando_el_primero_falla(): void
    {
        Http::fake([
            '*/models/gemini-flash-latest*' => Http::response(['error' => ['message' => 'high demand']], 503),
            '*/models/gemini-3.8-flash*' => $this->respuestaOk('Respuesta del modelo de reserva'),
        ]);

        $servicio = new GeminiService;

        $this->assertSame(
            'Respuesta del modelo de reserva',
            $servicio->analyzeFeedback(6, 'Cervical', 'Mejor', null)
        );

        // Se han probado los dos: el primero falló (con un reintento, porque
        // un 503 suele ser pasajero) y el segundo respondió.
        Http::assertSentCount(3);

        Http::assertSent(fn (Request $peticion) => str_contains($peticion->url(), 'gemini-3.8-flash'));
    }

    public function test_un_modelo_retirado_no_impide_usar_el_siguiente(): void
    {
        Http::fake([
            '*/models/gemini-flash-latest*' => Http::response(['error' => ['message' => 'no longer available']], 404),
            '*/models/gemini-3.8-flash*' => $this->respuestaOk('Sigue funcionando'),
        ]);

        $servicio = new GeminiService;

        $this->assertSame('Sigue funcionando', $servicio->analyzeFeedback(5, null, null, null));
    }

    public function test_con_la_clave_invalida_no_insiste_en_probar_mas_modelos(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 401),
        ]);

        $servicio = new GeminiService;

        $this->assertNull($servicio->analyzeFeedback(5, null, null, null));
        $this->assertSame('credenciales', $servicio->ultimoMotivo());

        // Un solo intento: cambiar de modelo no arregla una clave inválida.
        Http::assertSentCount(1);
    }

    public function test_la_cuota_agotada_se_distingue_de_una_saturacion(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        $servicio = new GeminiService;

        $this->assertNull($servicio->analyzeFeedback(5, null, null, null));
        $this->assertSame('cuota', $servicio->ultimoMotivo());

        // Con la cuota agotada no se insiste: se prueba cada modelo una vez.
        Http::assertSentCount(2);
    }

    public function test_el_chat_devuelve_un_mensaje_legible_y_no_un_codigo_http(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        $servicio = new GeminiService;

        $respuesta = $servicio->chatWithHistory([], '¿Cómo va el paciente?', 'Carlos García');

        $this->assertStringContainsString('cuota', mb_strtolower($respuesta));
        $this->assertStringNotContainsString('429', $respuesta);
        $this->assertStringNotContainsString('Error', $respuesta);
    }

    public function test_sin_clave_de_api_no_intenta_contactar_con_google(): void
    {
        config(['services.gemini.api_key' => null]);

        Http::fake();

        $servicio = new GeminiService;

        $this->assertNull($servicio->analyzeFeedback(5, null, null, null));
        $this->assertSame('credenciales', $servicio->ultimoMotivo());

        Http::assertNothingSent();
    }

    public function test_la_clave_no_aparece_en_la_url_de_la_peticion(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => $this->respuestaOk(),
        ]);

        (new GeminiService)->analyzeFeedback(5, null, null, null);

        Http::assertSent(function (Request $request) {
            return ! str_contains($request->url(), 'clave-de-prueba')
                && $request->hasHeader('x-goog-api-key', 'clave-de-prueba');
        });
    }

    public function test_el_job_no_reintenta_en_vano_si_la_cuota_esta_agotada(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota exceeded']], 429),
        ]);

        $feedback = $this->crearFeedback();

        // Si el job lanzara la excepción, la cola lo reintentaría tres veces
        // sin éxito (la cuota no se repone en quince segundos).
        (new AnalyzeFeedbackJob($feedback))->handle(new GeminiService);

        // Y se guarda el motivo para que el panel pueda explicarlo.
        $this->assertNull($feedback->fresh()->analisis_ia);
        $this->assertSame('cuota', $feedback->fresh()->analisis_error);
    }

    public function test_el_job_guarda_el_informe_cuando_la_ia_responde(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => $this->respuestaOk('Informe generado.'),
        ]);

        $feedback = $this->crearFeedback();

        (new AnalyzeFeedbackJob($feedback))->handle(new GeminiService);

        $this->assertSame('Informe generado.', $feedback->fresh()->analisis_ia);
    }

    private function crearFeedback(): Feedback
    {
        $fisio = User::factory()->fisio()->create();
        $paciente = User::factory()->pacienteDe($fisio)->create();
        $plan = Plan::factory()->paraFisio($fisio)->create(['paciente_id' => $paciente->id]);
        $planDia = PlanDia::create(['plan_id' => $plan->id, 'numero_dia' => 1]);

        return Feedback::create([
            'plan_dia_id' => $planDia->id,
            'dureza' => 6,
            'dolor' => 'Sin dolor',
            'evolucion' => 'Bien',
            'comentario' => 'Sesión correcta',
        ]);
    }
}
