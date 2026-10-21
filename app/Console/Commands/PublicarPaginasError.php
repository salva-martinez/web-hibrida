<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

/**
 * Publica las páginas de error como HTML estático.
 *
 * nginx sirve estas páginas directamente desde el disco (public/errors/*.html)
 * en lugar de pedírselas a la aplicación: así, si la aplicación está caída
 * (502, 504) o en mantenimiento, el visitante sigue viendo una página con el
 * estilo del proyecto y, sobre todo, en el subdominio correcto.
 *
 * La fuente de verdad son las vistas Blade de resources/views/errors: no hay
 * que mantener dos versiones del mismo diseño.
 */
class PublicarPaginasError extends Command
{
    protected $signature = 'errors:publicar';

    protected $description = 'Genera las páginas de error estáticas en public/errors/';

    public function handle(): int
    {
        $destino = public_path('errors');

        if (! is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        // Códigos que se publican. 502 y 504 los genera el servidor web, no la
        // aplicación, pero usan la misma plantilla.
        $codigos = [404, 500, 502, 503, 504];

        foreach ($codigos as $codigo) {
            $vista = "errors.{$codigo}";

            if (! View::exists($vista)) {
                $this->warn("  · No existe la vista {$vista}, se omite.");

                continue;
            }

            $html = View::make($vista)->render();
            $fichero = $destino.DIRECTORY_SEPARATOR.$codigo.'.html';

            file_put_contents($fichero, $html);
            $this->line("  · {$codigo}.html (".strlen($html).' bytes)');
        }

        $this->info('Páginas de error publicadas en public/errors/.');

        return self::SUCCESS;
    }
}
