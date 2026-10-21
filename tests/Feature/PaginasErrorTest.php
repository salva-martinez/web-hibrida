<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Las páginas de error deben existir, tener el estilo del proyecto y servirse
 * desde el subdominio correcto (nunca desde el dominio principal).
 */
class PaginasErrorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function codigos(): array
    {
        return [
            '404' => ['404'],
            '500' => ['500'],
            '502' => ['502'],
            '503' => ['503'],
            '504' => ['504'],
        ];
    }

    /**
     * @dataProvider codigos
     */
    public function test_existe_la_vista_de_error(string $codigo): void
    {
        $this->assertFileExists(resource_path("views/errors/{$codigo}.blade.php"));
    }

    /**
     * @dataProvider codigos
     */
    public function test_la_pagina_de_error_tiene_el_estilo_de_fisioapp(string $codigo): void
    {
        $this->actingAs(User::factory()->fisio()->create());

        $respuesta = $this->get('/ruta-que-no-existe-'.uniqid());

        // La app responde con su propia página de error.
        $respuesta->assertNotFound();
    }

    public function test_el_comando_publica_las_paginas_como_html_estatico(): void
    {
        $this->artisan('errors:publicar')->assertSuccessful();

        foreach ([404, 500, 502, 503, 504] as $codigo) {
            $fichero = public_path("errors/{$codigo}.html");

            $this->assertFileExists($fichero, "Falta la página estática {$codigo}.html");
            $this->assertStringContainsString('FisioApp', file_get_contents($fichero));
            $this->assertStringContainsString('💪', file_get_contents($fichero));
        }
    }

    public function test_los_nginx_sirven_las_paginas_de_error_en_su_propio_dominio(): void
    {
        $fisio = File::get(base_path('deploy/nginx/fisioapp.conf'));

        // error_page con redirección interna: la URL no cambia y el visitante
        // se queda en el subdominio de la aplicación.
        $this->assertStringContainsString('error_page 404 /errors/404.html;', $fisio);
        $this->assertStringContainsString('proxy_intercept_errors on;', $fisio);
        $this->assertStringContainsString('internal;', $fisio);
        $this->assertStringContainsString('server_name fisio.salvamartinez.es;', $fisio);
    }

    public function test_el_portfolio_tiene_su_propia_pagina_de_error(): void
    {
        $ruta = base_path('deploy/errors/portfolio/404.html');

        $this->assertFileExists($ruta);
        $contenido = File::get($ruta);

        // El mismo lenguaje visual que la landing.
        $this->assertStringContainsString('Salva Martinez Juan', $contenido);
        $this->assertStringContainsString('Cormorant Garamond', $contenido);
        $this->assertStringNotContainsString('backdrop-filter', $contenido);
    }

    public function test_reservas_tiene_su_propia_pagina_de_error(): void
    {
        $ruta = base_path('deploy/errors/reservas/404.html');

        $this->assertFileExists($ruta);
        $contenido = File::get($ruta);

        // El estilo de la app de reservas: fondo claro y azul de enlaces.
        $this->assertStringContainsString('#1769aa', $contenido);
        $this->assertStringContainsString('Gestión de Reservas', $contenido);
    }

    public function test_cada_proyecto_tiene_su_propia_pagina_de_error(): void
    {
        $this->artisan('errors:publicar')->assertSuccessful();

        // FisioApp: la genera el comando desde las vistas Blade.
        $fisio = File::get(public_path('errors/404.html'));

        // Portfolio y reservas: HTML estático versionado en el repositorio.
        $portfolio = File::get(base_path('deploy/errors/portfolio/404.html'));
        $reservas = File::get(base_path('deploy/errors/reservas/404.html'));

        // Tres páginas distintas: cada proyecto con su propio lenguaje visual.
        $this->assertNotSame($portfolio, $reservas);
        $this->assertNotSame($fisio, $portfolio);
        $this->assertNotSame($fisio, $reservas);

        // Y cada una con la paleta de su proyecto.
        $this->assertStringContainsString('#0ea5e9', $fisio);       // FisioApp: azul
        $this->assertStringContainsString('#8a1c14', $portfolio);    // Portfolio: sangre
        $this->assertStringContainsString('#1769aa', $reservas);     // Reservas: azul de la app
    }
}
