<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las URLs de vídeo solo pueden ser de YouTube.
 *
 * La validación "url" de Laravel acepta esquemas como "javascript:", que luego
 * se renderizan como href o como src de un iframe y acaban ejecutando
 * JavaScript en el navegador de quien los pulse.
 */
class UrlVideoSeguraTest extends TestCase
{
    use RefreshDatabase;

    private function crearEjercicio(User $fisio): Ejercicio
    {
        $estimulo = Estimulo::factory()->deFisio($fisio)->create();

        return Ejercicio::factory()->deFisio($fisio)->create(['estimulo_id' => $estimulo->id]);
    }

    public static function urlsInvalidas(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript con comentario' => ['javascript://comment%0aalert(1)'],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'http sin tls' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'otro host' => ['https://vimeo.com/123456789'],
            'host fraudulento' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'youtube sin video' => ['https://www.youtube.com/'],
            'id invalido' => ['https://www.youtube.com/watch?v=corto'],
        ];
    }

    /**
     * @dataProvider urlsInvalidas
     */
    public function test_se_rechazan_las_urls_que_no_son_de_youtube(string $url): void
    {
        $fisio = User::factory()->fisio()->create();
        $ejercicio = $this->crearEjercicio($fisio);

        $this->actingAs($fisio)
            ->put(route('admin.ejercicios.update', $ejercicio), [
                'nombre' => $ejercicio->nombre,
                'estimulo_id' => $ejercicio->estimulo_id,
                'video_url' => $url,
            ])
            ->assertSessionHasErrors('video_url');
    }

    public static function urlsValidas(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'watch sin www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'],
            'nocookie' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
        ];
    }

    /**
     * @dataProvider urlsValidas
     */
    public function test_se_aceptan_las_urls_de_youtube(string $url): void
    {
        $fisio = User::factory()->fisio()->create();
        $ejercicio = $this->crearEjercicio($fisio);

        $this->actingAs($fisio)
            ->put(route('admin.ejercicios.update', $ejercicio), [
                'nombre' => $ejercicio->nombre,
                'estimulo_id' => $ejercicio->estimulo_id,
                'video_url' => $url,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('dQw4w9WgXcQ', Ejercicio::youtubeVideoId($url));
    }

    public function test_una_url_vacia_es_valida_porque_significa_sin_video(): void
    {
        $fisio = User::factory()->fisio()->create();
        $ejercicio = $this->crearEjercicio($fisio);

        $this->actingAs($fisio)
            ->put(route('admin.ejercicios.update', $ejercicio), [
                'nombre' => $ejercicio->nombre,
                'estimulo_id' => $ejercicio->estimulo_id,
                'video_url' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($ejercicio->fresh()->tieneVideo());
    }

    public function test_la_url_de_embed_se_construye_siempre_desde_el_identificador(): void
    {
        // Aunque la URL original sea manipulada, el embed sale del id validado.
        $ejercicio = new Ejercicio(['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $ejercicio->embed_url);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $ejercicio->youtube_url);
    }

    public function test_una_url_no_valida_no_produce_embed(): void
    {
        $ejercicio = new Ejercicio(['video_url' => 'javascript://comment%0aalert(1)']);

        $this->assertNull($ejercicio->embed_url);
        $this->assertNull($ejercicio->youtube_url);
        $this->assertFalse($ejercicio->tieneVideo());
    }

    public function test_una_url_con_el_host_suplantado_no_produce_embed(): void
    {
        $ejercicio = new Ejercicio(['video_url' => 'https://youtube.com.evil.example/embed/dQw4w9WgXcQ']);

        $this->assertNull($ejercicio->embed_url);
        $this->assertFalse($ejercicio->tieneVideo());
    }

    public function test_la_vista_de_ejercicios_no_enlaza_a_la_url_original(): void
    {
        $fisio = User::factory()->fisio()->create();
        $ejercicio = $this->crearEjercicio($fisio);
        $ejercicio->update(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

        $this->actingAs($fisio)
            ->get(route('admin.ejercicios.index'))
            ->assertOk()
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }
}
