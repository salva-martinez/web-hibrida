<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanEjercicio;
use App\Models\User;
use App\Models\Ejercicio;
use App\Models\Estimulo;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function index()
    {
        // El aislamiento global (FisioScope) ya limita la lista a los planes propios.
        $planes = Plan::with(['paciente', 'planDias.feedback'])->latest('fecha')->get();
        return view('admin.planes.index', compact('planes'));
    }

    public function create(Request $request)
    {
        $pacientes = User::pacientes()->orderBy('nombre')->get();
        $estimulos = Estimulo::with('ejercicios')->orderBy('orden')->get();

        // 1. Logic for Cloning/Selector
        if ($request->has('paciente_id') && !$request->has('skip_selector') && !$request->has('clone_id')) {
            $paciente = User::findOrFail($request->paciente_id);
            $this->autorizarPaciente($paciente);
            $ultimoPlan = $paciente->planesComoPaciente()->first(); // latest('fecha') is default in model

            if ($ultimoPlan) {
                return view('admin.planes.selector', compact('paciente', 'ultimoPlan'));
            }
        }

        $clonedPlan = null;
        if ($request->has('clone_id')) {
            // findOrFail + global scope: no se puede clonar el plan de otro fisio.
            $clonedPlan = Plan::with('planDias.planEjercicios')->findOrFail($request->clone_id);
        }

        return view('admin.planes.create', compact('pacientes', 'estimulos', 'clonedPlan'));
    }

    public function store(Request $request, PlanService $planService)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:users,id',
            'titulo' => 'required|string|max:255',
            'fecha' => 'required|date',
            'dias' => 'required|array|min:1',
            'dias.*.notas_fisio' => 'nullable|string',
            'dias.*.ejercicios' => 'required|array|min:1',
            'dias.*.ejercicios.*.ejercicio_id' => 'required|exists:ejercicios,id',
            'dias.*.ejercicios.*.series' => 'required|integer|min:1|max:350',
            'dias.*.ejercicios.*.repeticiones' => 'required|numeric|min:1|max:350',
            'dias.*.ejercicios.*.intensidad' => 'required|integer|min:1|max:10',
            'dias.*.ejercicios.*.kg' => 'nullable|numeric|min:1|max:350',
            'dias.*.ejercicios.*.descanso' => 'nullable|string|max:20',
        ]);

        // El plan solo puede asignarse a un paciente de esta cartera.
        $this->autorizarPaciente(User::findOrFail($validated['paciente_id']));

        $validated['activo'] = request()->boolean('activo', true);

        $planService->createPlan($validated);

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan creado correctamente.');
    }

    public function show(Plan $plan)
    {
        $plan->load(['paciente', 'fisio', 'planDias.planEjercicios.ejercicio.estimulo', 'planDias.feedback']);
        return view('admin.planes.show', compact('plan'));
    }

    public function edit(Plan $plan)
    {
        $plan->load('planDias.planEjercicios.ejercicio');
        $pacientes = User::pacientes()->orderBy('nombre')->get();
        $estimulos = Estimulo::with('ejercicios')->orderBy('orden')->get();
        return view('admin.planes.edit', compact('plan', 'pacientes', 'estimulos'));
    }

    public function update(Request $request, Plan $plan, PlanService $planService)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:users,id',
            'titulo' => 'required|string|max:255',
            'fecha' => 'required|date',
            'activo' => 'boolean',
            'dias' => 'required|array|min:1',
            'dias.*.notas_fisio' => 'nullable|string',
            'dias.*.ejercicios' => 'required|array|min:1',
            'dias.*.ejercicios.*.ejercicio_id' => 'required|exists:ejercicios,id',
            'dias.*.ejercicios.*.series' => 'required|integer|min:1',
            'dias.*.ejercicios.*.repeticiones' => 'required|string',
            'dias.*.ejercicios.*.intensidad' => 'required|integer|min:0|max:10',
            'dias.*.ejercicios.*.kg' => 'nullable|numeric|min:0',
            'dias.*.ejercicios.*.descanso' => 'nullable|string',
        ]);

        $this->autorizarPaciente(User::findOrFail($validated['paciente_id']));

        $validated['activo'] = $request->boolean('activo');

        $planService->updatePlan($plan, $validated);

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan actualizado correctamente.');
    }

    /**
     * Archiva un plan (borrado lógico).
     *
     * El plan deja de ser visible para el paciente, pero se conservan sus días,
     * los ejercicios pautados y todo el feedback clínico ya registrado.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan archivado. El historial del paciente se conserva y puede restaurarse desde la Papelera.');
    }

    /**
     * Garantiza que el paciente pertenece a este fisioterapeuta.
     */
    private function autorizarPaciente(User $paciente): void
    {
        abort_unless(
            $paciente->isPaciente() && $paciente->perteneceAlFisioAutenticado(),
            403,
            'Este paciente no pertenece a tu cartera.'
        );
    }
}
