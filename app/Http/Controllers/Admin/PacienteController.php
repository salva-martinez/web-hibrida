<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $query = User::pacientes();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido1', 'like', "%{$search}%")
                  ->orWhere('apellido2', 'like', "%{$search}%");
            });
        }

        $pacientes = $query->latest()->get();
        return view('admin.pacientes.index', compact('pacientes'));
    }

    public function create()
    {
        return view('admin.pacientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
        ]);

        User::create([
            ...$validated,
            'password' => bcrypt('password'), // Default password
            'role' => 'paciente',
        ]);

        return redirect()->route('admin.pacientes.index')
            ->with('success', 'Paciente creado correctamente.');
    }

    public function show(User $paciente)
    {
        $paciente->load(['planesComoPaciente.feedback', 'planesComoPaciente.planEjercicios.ejercicio.estimulo']);
        return view('admin.pacientes.show', compact('paciente'));
    }

    public function edit(User $paciente)
    {
        return view('admin.pacientes.edit', compact('paciente'));
    }

    public function update(Request $request, User $paciente)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $paciente->id,
        ]);

        $paciente->update($validated);

        return redirect()->route('admin.pacientes.index')
            ->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(User $paciente)
    {
        $paciente->delete();
        return redirect()->route('admin.pacientes.index')
            ->with('success', 'Paciente eliminado correctamente.');
    }
}
