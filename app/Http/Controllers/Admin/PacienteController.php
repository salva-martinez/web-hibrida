<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        // scopePacientes() ya filtra por propietario: un fisio solo ve sus pacientes.
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
            'fecha_nacimiento' => 'required|date',
            'email' => 'nullable|email|unique:users,email',
        ]);

        $paciente = User::create([
            ...$validated,
            'password' => Str::random(32), // Not used for login, patients authenticate via name + fecha_nacimiento
            'role' => 'paciente',
            // El paciente queda vinculado a este fisioterapeuta.
            'fisio_id' => Auth::id(),
        ]);

        // El email del paciente es de contacto, no de acceso: no requiere verificación.
        $paciente->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.pacientes.index')
            ->with('success', 'Paciente creado correctamente.');
    }

    public function show(User $paciente)
    {
        $this->autorizarPaciente($paciente);

        $paciente->load(['planesComoPaciente.planDias.feedback', 'planesComoPaciente.planDias.planEjercicios.ejercicio.estimulo']);
        return view('admin.pacientes.show', compact('paciente'));
    }

    public function edit(User $paciente)
    {
        $this->autorizarPaciente($paciente);

        return view('admin.pacientes.edit', compact('paciente'));
    }

    public function update(Request $request, User $paciente)
    {
        $this->autorizarPaciente($paciente);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido1' => 'required|string|max:255',
            'apellido2' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'email' => 'nullable|email|unique:users,email,' . $paciente->id,
        ]);

        $paciente->update($validated);

        return redirect()->route('admin.pacientes.index')
            ->with('success', 'Paciente actualizado correctamente.');
    }

    /**
     * Desactiva un paciente (borrado lógico).
     *
     * La ficha y todo su historial clínico (planes, feedback, análisis de IA) se
     * conservan íntegros y pueden restaurarse desde la papelera. No existe borrado
     * físico de cuentas.
     */
    public function destroy(User $paciente)
    {
        $this->autorizarPaciente($paciente);

        $paciente->delete();

        return redirect()->route('admin.pacientes.index')
            ->with('success', 'Paciente desactivado. Su historial se conserva y puede restaurarse desde la Papelera.');
    }

    /**
     * Garantiza que el recurso es un paciente de este fisioterapeuta.
     *
     * Impide tanto el acceso cruzado entre profesionales como el borrado o la
     * edición de cuentas de otros fisioterapeutas.
     */
    private function autorizarPaciente(User $paciente): void
    {
        abort_unless(
            $paciente->isPaciente() && $paciente->perteneceAlFisioAutenticado(),
            403,
            'Este recurso no pertenece a tu cartera de pacientes.'
        );
    }
}
