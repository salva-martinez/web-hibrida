<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Feedback;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $planActivo = Plan::where('paciente_id', $user->id)
            ->where('activo', true)
            ->latest('fecha')
            ->first();

        if ($planActivo) {
            return redirect()->route('paciente.plan.show', $planActivo);
        }

        $planes = Plan::where('paciente_id', $user->id)
            ->orderBy('fecha', 'desc')
            ->get();

        return view('paciente.dashboard', compact('planes'));
    }
}
