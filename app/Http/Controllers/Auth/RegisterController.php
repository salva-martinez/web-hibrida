<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FisioInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showForm(string $token)
    {
        $invitacion = FisioInvitation::where('token', $token)->first();

        if (!$invitacion || !$invitacion->isValid()) {
            abort(404);
        }

        return view('auth.register', compact('invitacion'));
    }

    public function register(Request $request, string $token)
    {
        $invitacion = FisioInvitation::where('token', $token)->first();

        if (!$invitacion || !$invitacion->isValid()) {
            abort(404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            ...$validated,
            'password' => bcrypt($validated['password']),
            'role' => 'fisio',
        ]);

        $invitacion->markUsedBy($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('success', '¡Cuenta creada correctamente! Bienvenido/a a FisioApp.');
    }
}
