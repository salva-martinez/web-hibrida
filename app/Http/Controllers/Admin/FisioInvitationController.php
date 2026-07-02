<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FisioInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FisioInvitationController extends Controller
{
    public function index()
    {
        $invitaciones = FisioInvitation::with(['invitedBy', 'usedBy'])
            ->latest()
            ->get();

        return view('admin.invitaciones.index', compact('invitaciones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
        ]);

        $invitacion = FisioInvitation::generate(Auth::user(), $validated['email'] ?? null);

        return redirect()->route('admin.invitaciones.index')
            ->with('success', 'Invitación creada. Comparte el siguiente enlace con tu compañero/a.')
            ->with('invite_link', route('register.show', $invitacion->token));
    }

    public function destroy(FisioInvitation $invitacion)
    {
        $invitacion->delete();

        return redirect()->route('admin.invitaciones.index')
            ->with('success', 'Invitación revocada.');
    }
}
