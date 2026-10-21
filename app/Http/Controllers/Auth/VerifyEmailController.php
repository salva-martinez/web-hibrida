<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Pantalla de "verifica tu email" (destino tras registrarse).
     */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.verify-email');
    }

    /**
     * Confirma la dirección de email a partir del enlace firmado.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Tu email ya estaba verificado.');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            // Enlace caducado o manipulado: se avisa al usuario en lugar de
            // dejarle en un 403 genérico.
            return redirect()->route('verification.notice')
                ->with('error', 'El enlace de verificación no es válido o ha caducado. Pide uno nuevo.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('admin.dashboard')
            ->with('success', '¡Email verificado! Ya puedes usar FisioApp.');
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

        return back()->with('success', 'Te hemos reenviado el email de verificación.');
    }
}
