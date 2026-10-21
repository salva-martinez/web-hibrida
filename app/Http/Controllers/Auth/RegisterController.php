<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class RegisterController extends Controller
{
    /**
     * Formulario de alta de fisioterapeutas (registro abierto).
     *
     * No hace falta invitación: cada fisio se crea su propia "isla" de pacientes,
     * planes y catálogo, por lo que no puede ver los datos de ningún colega.
     * La cuenta queda inactiva hasta que verifique su email.
     */
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'Ya existe una cuenta registrada con ese email.',
        ]);

        $user = User::create([
            'nombre' => $validated['nombre'],
            'apellido1' => $validated['apellido1'],
            'apellido2' => $validated['apellido2'],
            'email' => $validated['email'],
            // El cast 'hashed' del modelo se encarga de cifrar la contraseña.
            'password' => $validated['password'],
            'role' => 'fisio',
            'fisio_id' => null,
        ]);

        // Se envía el email de verificación.
        $user->sendEmailVerificationNotification();
        $this->registrarEnlaceSiNoHayCorreo($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', '¡Cuenta creada! Revisa tu correo para activarla.');
    }

    /**
     * Con el mailer en modo 'log' (que es lo que hay por defecto en desarrollo)
     * el email no llega a ningún sitio: solo se escribe en el log, y el cuerpo
     * del mensaje no queda en texto plano. Por eso el enlace de verificación se
     * registra aparte, para poder activar la cuenta a mano.
     *
     * En producción, con un SMTP configurado, esto no hace nada.
     */
    private function registrarEnlaceSiNoHayCorreo(User $user): void
    {
        if (config('mail.default') !== 'log') {
            return;
        }

        Log::warning('Enlace de verificación (MAIL_MAILER=log, el email no se envía): '.$this->enlaceDeVerificacion($user));
    }

    /**
     * Enlace firmado de verificación de email.
     */
    private function enlaceDeVerificacion(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes((int) config('auth.verification.expire', 60)), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }
}
