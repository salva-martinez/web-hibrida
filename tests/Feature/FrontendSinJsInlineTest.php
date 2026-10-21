<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * El JavaScript vive en resources/js/ como módulos ES, no embebido en las
 * vistas. Así se puede cachear, versionar y testear, y evita que un dato de la
 * base de datos acabe dentro de una etiqueta de JavaScript.
 */
class FrontendSinJsInlineTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function vistas(): array
    {
        return [
            'editor de planes' => ['admin/planes/create.blade.php'],
            'detalle de plan' => ['admin/planes/show.blade.php'],
            'selector de plan' => ['admin/planes/selector.blade.php'],
            'plan del paciente' => ['paciente/plan.blade.php'],
            'login' => ['auth/login.blade.php'],
            'registro' => ['auth/register.blade.php'],
            'verificacion de email' => ['auth/verify-email.blade.php'],
        ];
    }

    /**
     * @dataProvider vistas
     */
    public function test_ninguna_vista_tiene_javascript_embebido(string $vista): void
    {
        $contenido = File::get(resource_path('views/'.$vista));

        $this->assertStringNotContainsString(
            '<script',
            $contenido,
            "La vista {$vista} tiene JavaScript embebido: muévelo a resources/js/."
        );
    }

    /**
     * @dataProvider vistas
     */
    public function test_ninguna_vista_usa_handlers_inline(string $vista): void
    {
        $contenido = File::get(resource_path('views/'.$vista));

        $this->assertDoesNotMatchRegularExpression(
            '/on(click|change|input|submit|load)="/',
            $contenido,
            "La vista {$vista} usa un handler inline: delega en un módulo de resources/js/."
        );
    }

    public function test_la_tabla_de_ejercicios_es_un_componente_y_no_esta_duplicada(): void
    {
        $this->assertFileExists(resource_path('views/components/ejercicios-tabla.blade.php'));

        foreach (['admin/planes/show.blade.php', 'paciente/plan.blade.php'] as $vista) {
            $contenido = File::get(resource_path('views/'.$vista));

            $this->assertStringContainsString('x-ejercicios-tabla', $contenido);
            $this->assertStringNotContainsString('class="exercise-table"', $contenido);
        }
    }

    public function test_todos_los_modulos_js_importados_existen(): void
    {
        $app = File::get(resource_path('js/app.js'));

        preg_match_all("/from '(\.\/[\w-]+)'/", $app, $coincidencias);

        $this->assertNotEmpty($coincidencias[1], 'app.js no importa ningún módulo.');

        foreach ($coincidencias[1] as $ruta) {
            $this->assertFileExists(resource_path('js/'.$ruta.'.js'));
        }
    }
}
