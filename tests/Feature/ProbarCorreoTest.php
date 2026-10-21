<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * El comando "mail:probar" permite comprobar la configuración de correo sin
 * tener que desplegar a ciegas.
 */
class ProbarCorreoTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_la_configuracion_de_correo(): void
    {
        $this->artisan('mail:probar --mostrar')
            ->expectsOutputToContain('MAIL_MAILER')
            ->assertSuccessful();
    }

    public function test_avisa_si_el_correo_sigue_en_modo_log(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('mail:probar')
            ->expectsOutputToContain('sigue en "log"')
            ->assertFailed();
    }

    public function test_envia_un_email_de_prueba(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp-relay.brevo.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'usuario@ejemplo.com',
            'mail.mailers.smtp.password' => 'clave',
            'mail.from.address' => 'no-reply@salvamartinez.es',
            'mail.from.name' => 'FisioApp',
        ]);

        Mail::shouldReceive('raw')->once();

        $this->artisan('mail:probar destinatario@ejemplo.com')
            ->expectsOutputToContain('Enviado')
            ->assertSuccessful();
    }

    public function test_informa_del_error_si_el_envio_falla(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'no-reply@salvamartinez.es',
        ]);

        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('SMTP error'));

        $this->artisan('mail:probar destinatario@ejemplo.com')
            ->expectsOutputToContain('No se ha podido enviar')
            ->assertFailed();
    }
}
