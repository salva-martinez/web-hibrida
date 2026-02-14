<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanEjercicio;
use App\Models\User;
use App\Models\Ejercicio;
use App\Models\Estimulo;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $planes = Plan::with(['paciente', 'feedback'])->latest('fecha')->get();
        return view('admin.planes.index', compact('planes'));
    }

    public function create(Request $request)
    {
        $pacientes = User::pacientes()->orderBy('nombre')->get();
        $estimulos = Estimulo::with('ejercicios')->orderBy('orden')->get();

        // 1. Logic for Cloning/Selector
        if ($request->has('paciente_id') && !$request->has('skip_selector') && !$request->has('clone_id')) {
            $paciente = User::findOrFail($request->paciente_id);
            $ultimoPlan = $paciente->planesComoPaciente()->first(); // latest('fecha') is default in model

            if ($ultimoPlan) {
                return view('admin.planes.selector', compact('paciente', 'ultimoPlan'));
            }
        }

        $clonedPlan = null;
        if ($request->has('clone_id')) {
            $clonedPlan = Plan::with('planEjercicios')->find($request->clone_id);
        }

        return view('admin.planes.create', compact('pacientes', 'estimulos', 'clonedPlan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:users,id',
            'titulo' => 'required|string|max:255',
            'fecha' => 'required|date',
            'ejercicios' => 'required|array|min:1',
            'ejercicios.*.ejercicio_id' => 'required|exists:ejercicios,id',
            'ejercicios.*.series' => 'required|integer|min:1',
            'ejercicios.*.repeticiones' => 'required|string',
            'ejercicios.*.intensidad' => 'required|integer|min:0|max:10',
            'ejercicios.*.kg' => 'nullable|numeric|min:0',
            'ejercicios.*.descanso' => 'nullable|string',
        ]);

        // Auto-inactivate previous active plans for this patient if the new one is active
        if (request()->boolean('activo', true)) { // Default to true if not present, though 'activo' isn't in validate it's set in create
             Plan::where('paciente_id', $validated['paciente_id'])
                ->where('activo', true)
                ->update(['activo' => false]);
        }

        $plan = Plan::create([
            'paciente_id' => $validated['paciente_id'],
            'fisio_id' => auth()->id(),
            'titulo' => $validated['titulo'],
            'fecha' => $validated['fecha'],
            'activo' => true,
        ]);

        foreach ($validated['ejercicios'] as $index => $ej) {
            PlanEjercicio::create([
                'plan_id' => $plan->id,
                'ejercicio_id' => $ej['ejercicio_id'],
                'series' => $ej['series'],
                'repeticiones' => $ej['repeticiones'],
                'intensidad' => $ej['intensidad'],
                'kg' => $ej['kg'] ?? null,
                'descanso' => $ej['descanso'] ?? null,
                'orden' => $index,
            ]);
        }

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan creado correctamente.');
    }

    public function show(Plan $plan)
    {
        $plan->load(['paciente', 'fisio', 'planEjercicios.ejercicio.estimulo', 'feedback']);
        $ejerciciosPorEstimulo = $plan->ejerciciosPorEstimulo();
        return view('admin.planes.show', compact('plan', 'ejerciciosPorEstimulo'));
    }

    public function edit(Plan $plan)
    {
        $plan->load('planEjercicios.ejercicio');
        $pacientes = User::pacientes()->orderBy('nombre')->get();
        $estimulos = Estimulo::with('ejercicios')->orderBy('orden')->get();
        return view('admin.planes.edit', compact('plan', 'pacientes', 'estimulos'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:users,id',
            'titulo' => 'required|string|max:255',
            'fecha' => 'required|date',
            'activo' => 'boolean',
            'ejercicios' => 'required|array|min:1',
            'ejercicios.*.ejercicio_id' => 'required|exists:ejercicios,id',
            'ejercicios.*.series' => 'required|integer|min:1',
            'ejercicios.*.repeticiones' => 'required|string',
            'ejercicios.*.intensidad' => 'required|integer|min:0|max:10',
            'ejercicios.*.kg' => 'nullable|numeric|min:0',
            'ejercicios.*.descanso' => 'nullable|string',
        ]);

        $plan->update([
            'paciente_id' => $validated['paciente_id'],
            'titulo' => $validated['titulo'],
            'fecha' => $validated['fecha'],
            'activo' => $request->boolean('activo'),
        ]);

        // Replace all exercises
        $plan->planEjercicios()->delete();
        foreach ($validated['ejercicios'] as $index => $ej) {
            PlanEjercicio::create([
                'plan_id' => $plan->id,
                'ejercicio_id' => $ej['ejercicio_id'],
                'series' => $ej['series'],
                'repeticiones' => $ej['repeticiones'],
                'intensidad' => $ej['intensidad'],
                'kg' => $ej['kg'] ?? null,
                'descanso' => $ej['descanso'] ?? null,
                'orden' => $index,
            ]);
        }

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan actualizado correctamente.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan eliminado correctamente.');
    }
}
