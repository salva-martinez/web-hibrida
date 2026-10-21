<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Comprueba la configuración de correo y envía un mensaje de prueba.
 *
 * Sirve para no tener que desplegar "a ciegas": primero se comprueba que la
 * conexión SMTP funciona y luego se manda un email real a una dirección.
 */
class ProbarCorreo extends Command
{
    protected $signature = 'mail:probar
                            {destinatario? : Dirección a la que enviar la prueba (por defecto, la del remitente)}
                            {--mostrar : Muestra la configuración de correo sin enviar nada}';

    protected $description = 'Comprueba la configuración de correo y envía un email de prueba';

    public function handle(): int
    {
        $this->line('');
        $this->line('── Configuración de correo ──');
        $this->line('  MAIL_MAILER      : '.config('mail.default'));
        $this->line('  MAIL_HOST        : '.config('mail.mailers.smtp.host'));
        $this->line('  MAIL_PORT        : '.config('mail.mailers.smtp.port'));
        $this->line('  MAIL_USERNAME    : '.config('mail.mailers.smtp.username'));
        $this->line('  MAIL_PASSWORD    : '.(config('mail.mailers.smtp.password') ? '******** (configurada)' : 'SIN CONFIGURAR'));
        $this->line('  MAIL_ENCRYPTION  : '.config('mail.mailers.smtp.scheme'));
        $this->line('  MAIL_FROM        : '.config('mail.from.address').' ('.config('mail.from.name').')');
        $this->line('');

        if ($this->option('mostrar')) {
            return self::SUCCESS;
        }

        if (config('mail.default') === 'log') {
            $this->error('  MAIL_MAILER sigue en "log": los correos no se envían, solo se escriben en el log.');
            $this->line('  Configura un SMTP real (Brevo, Resend, SES...) y ejecuta: php artisan config:clear');
            $this->line('');

            return self::FAILURE;
        }

        $destinatario = $this->argument('destinatario') ?: config('mail.from.address');

        if (! $destinatario) {
            $this->error('  No hay dirección de destino. Indica una: php artisan mail:probar tu@email.com');

            return self::FAILURE;
        }

        $this->line("── Enviando prueba a {$destinatario} ──");

        try {
            Mail::raw(
                "Este es un email de prueba de FisioApp.\n\nSi lo estás leyendo, el correo saliente está bien configurado.",
                function ($message) use ($destinatario) {
                    $message->to($destinatario)
                        ->subject('FisioApp · prueba de correo');
                }
            );

            $this->info('  ✅ Enviado. Revisa la bandeja (y la de spam) para confirmar que ha llegado.');
            $this->line('');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('  ❌ No se ha podido enviar: '.get_class($e).' → '.$e->getMessage());
            $this->line('');
            $this->line('  Causas habituales:');
            $this->line('    · El remitente no está verificado en el proveedor.');
            $this->line('    · La contraseña SMTP es la "SMTP key", no la API key.');
            $this->line('    · El dominio no está autenticado (DKIM/SPF) en el DNS.');
            $this->line('    · MAIL_SCHEME: usa "null" con el puerto 587 (TLS negociado),');
            $this->line('      "smtps" con el 465 (TLS implícito) y "smtp" sin cifrado.');
            $this->line('');

            Log::error('Prueba de correo fallida', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
