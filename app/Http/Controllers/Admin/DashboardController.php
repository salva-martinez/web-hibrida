<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Ejercicio;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pacientes' => User::pacientes()->count(),
            'planes_activos' => Plan::where('activo', true)->count(),
            'ejercicios' => Ejercicio::count(),
        ];

        // Solo los feedbacks de los planes de este fisio: la relación planDia.plan
        // ya está aislada por el global scope, así que whereHas filtra por ella.
        $ultimosFeedbacks = Feedback::whereHas('planDia.plan')
            ->with('planDia.plan.paciente')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'ultimosFeedbacks'));
    }
}
