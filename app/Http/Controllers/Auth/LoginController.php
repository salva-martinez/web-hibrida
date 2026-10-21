<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle fisio login (email + password) or paciente login (nombre + apellidos).
     */
    public function login(Request $request)
    {
        $loginType = $request->input('login_type', 'fisio');

        if ($loginType === 'fisio') {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'fisio',
            ], $request->boolean('remember'))) {
                // La cuenta debe estar activada: sin verificar el email no se entra.
                if (! Auth::user()->hasVerifiedEmail()) {
                    Auth::logout();
                    $request->session()->regenerate();

                    return back()
                        ->withErrors(['email' => 'Debes verificar tu email para poder entrar. Revisa tu bandeja de entrada.'])
                        ->withInput();
                }

                $request->session()->regenerate();

                return redirect()->intended('/admin/dashboard');
            }

            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        // Paciente login
        $request->validate([
            'nombre' => 'required|string',
            'apellido1' => 'required|string',
            'apellido2' => 'required|string',
            'fecha_nacimiento' => 'required|date',
        ]);

        $user = User::where('nombre', $request->nombre)
            ->where('apellido1', $request->apellido1)
            ->where('apellido2', $request->apellido2)
            ->where('fecha_nacimiento', $request->fecha_nacimiento)
            ->where('role', 'paciente')
            ->first();

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/paciente/dashboard');
        }

        return back()->withErrors(['nombre' => 'No se ha encontrado ningún paciente con esos datos.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
