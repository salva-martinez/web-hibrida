<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanRequest;
use App\Models\Estimulo;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Http\Request;

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
        if ($request->has('paciente_id') && ! $request->has('skip_selector') && ! $request->has('clone_id')) {
            // scopePacientes() filtra por propietario: si el paciente no es
            // tuyo, findOrFail devuelve 404 y no se muestra su información.
            $paciente = User::pacientes()->findOrFail($request->paciente_id);
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

    public function store(PlanRequest $request, PlanService $planService)
    {
        $validated = $request->validated();

        // El plan nace activo salvo que se indique lo contrario.
        $validated['activo'] = $request->boolean('activo', true);

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

    public function update(PlanRequest $request, Plan $plan, PlanService $planService)
    {
        $validated = $request->validated();

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
}
