<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Pantalla de "verifica tu email" (destino tras registrarse).
     */
    public function notice(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.verify-email');
    }

    /**
     * Confirma la dirección de email a partir del enlace firmado.
     *
     * La ruta NO exige sesión iniciada: el enlace se recibe por email y se abre
     * desde cualquier dispositivo (normalmente el móvil, donde no hay sesión).
     * La seguridad la aporta la firma del enlace (middleware "signed"), que no se
     * puede falsificar sin la APP_KEY, más la comprobación del hash del email.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::find($id);

        if (! $user) {
            return $this->fallo('El enlace de verificación no es válido.');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->fallo('El enlace de verificación no corresponde a esta cuenta.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Si no hay sesión (por ejemplo, se abre desde el móvil) se inicia una
        // con la cuenta verificada. Si ya hay sesión de otro usuario, se
        // respeta: no se le cambia la sesión a otra persona.
        if (! Auth::check()) {
            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard')
                ->with('success', '¡Email verificado! Ya puedes usar FisioApp.');
        }

        return redirect()->route('admin.dashboard')
            ->with('success', '¡Email verificado!');
    }

    /**
     * Reenvía el email de verificación.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        // Con MAIL_MAILER=log el email no se envía: el enlace se deja en el log.
        if (config('mail.default') === 'log') {
            Log::warning('Enlace de verificación (MAIL_MAILER=log, el email no se envía): '.URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $request->user()->getKey(),
                    'hash' => sha1($request->user()->getEmailForVerification()),
                ]
            ));
        }

        return back()->with('success', 'Te hemos reenviado el email de verificación.');
    }

    /**
     * El enlace no vale: se vuelve al login con un mensaje claro, en vez de
     * dejar al usuario en un bucle de redirecciones.
     */
    private function fallo(string $mensaje)
    {
        return redirect()->route('login')->withErrors(['email' => $mensaje]);
    }
}
