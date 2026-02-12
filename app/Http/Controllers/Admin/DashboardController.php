<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Ejercicio;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pacientes' => User::pacientes()->count(),
            'planes_activos' => Plan::where('activo', true)->count(),
            'ejercicios' => Ejercicio::count(),
        ];

        $ultimosFeedbacks = \App\Models\Feedback::with('plan.paciente')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'ultimosFeedbacks'));
    }
}
