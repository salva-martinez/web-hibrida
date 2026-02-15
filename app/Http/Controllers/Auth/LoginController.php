<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
        ]);

        $user = User::where('nombre', $request->nombre)
            ->where('apellido1', $request->apellido1)
            ->where('apellido2', $request->apellido2)
            ->where('role', 'paciente')
            ->first();

        if ($user) {
            Auth::login($user, true);
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
