<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * El JavaScript vive en resources/js/ como módulos ES, no embebido en las vistas.
 *
 * Motivo: embebido no se puede cachear, versionar ni testear, y se duplicaba
 * entre vistas (el editor de planes, el chat y el modal de vídeo). Además,
 * cualquier dato de la base de datos interpolado en un <script> es un vector
 * de XSS.
 */
class FrontendEstructuraTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function vistas(): array
    {
        return [
            'editor de planes' => ['admin/planes/create.blade.php'],
            'detalle de plan' => ['admin/planes/show.blade.php'],
            'plan del paciente' => ['paciente/plan.blade.php'],
            'login' => ['auth/login.blade.php'],
        ];
    }

    /**
     * @dataProvider vistas
     */
    public function test_ninguna_vista_tiene_javascript_inline(string $vista): void
    {
        $contenido = File::get(resource_path('views/'.$vista));

        // Solo se admite el bloque JSON de datos (type="application/json").
        $scripts = [];
        preg_match_all('/<script(?![^>]*type="application\/json")[^>]*>(.*?)<\/script>/s', $contenido, $scripts);

        $this->assertEmpty(
            $scripts[1],
            "La vista {$vista} tiene JavaScript inline. Muévelo a resources/js/."
        );
    }

    /**
     * @dataProvider vistas
     */
    public function test_ninguna_vista_usa_handlers_inline_que_dependan_de_funciones_js(string $vista): void
    {
        $contenido = File::get(resource_path('views/'.$vista));

        // Los onclick inline ya no funcionan: el JS está en módulos y usa
        // addEventListener. Si aparece un onclick con una función de JS, es un error.
        $this->assertDoesNotMatchRegularExpression(
            '/onclick="[a-zA-Z_$]+\(/',
            $contenido,
            "La vista {$vista} usa un onclick inline que ya no está definido."
        );
    }

    public function test_los_modulos_js_existen_y_estan_importados(): void
    {
        $modulos = [
            'ai-status.js',
            'chat-ia.js',
            'feedback-form.js',
            'login-tabs.js',
            'plan-editor.js',
            'video-modal.js',
        ];

        foreach ($modulos as $modulo) {
            $this->assertFileExists(resource_path('js/'.$modulo));
        }

        $app = File::get(resource_path('js/app.js'));

        foreach ($modulos as $modulo) {
            // En app.js se importan sin la extension (.js).
            $this->assertStringContainsString(
                './'.pathinfo($modulo, PATHINFO_FILENAME),
                $app
            );
        }
    }

    public function test_no_se_usa_axios_porque_la_app_usa_fetch(): void
    {
        $this->assertFileDoesNotExist(resource_path('js/axios.js'));

        // El bootstrap no debe importar axios (aunque lo mencione en un comentario).
        $bootstrap = File::get(resource_path('js/bootstrap.js'));
        $this->assertStringNotContainsString("from 'axios'", $bootstrap);
        $this->assertStringNotContainsString('window.axios', $bootstrap);

        // El bundle no debe arrastrar axios.
        $paquete = json_decode(File::get(base_path('package.json')), true);
        $this->assertArrayNotHasKey('axios', $paquete['devDependencies'] ?? []);
    }
}
