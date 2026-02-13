<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Feedback;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function show(Plan $plan)
    {
        // Ensure patient can only view their own plans
        if ($plan->paciente_id !== auth()->id()) {
            abort(403);
        }

        $plan->load(['planEjercicios.ejercicio.estimulo', 'feedback']);
        $ejerciciosPorEstimulo = $plan->ejerciciosPorEstimulo();

        // Navigation: previous and next plans
        $user = auth()->user();
        $planAnterior = Plan::where('paciente_id', $user->id)
            ->where('fecha', '<', $plan->fecha)
            ->orderBy('fecha', 'desc')
            ->first();

        $planSiguiente = Plan::where('paciente_id', $user->id)
            ->where('fecha', '>', $plan->fecha)
            ->orderBy('fecha', 'asc')
            ->first();

        return view('paciente.plan', compact('plan', 'ejerciciosPorEstimulo', 'planAnterior', 'planSiguiente'));
    }

    public function storeFeedback(Request $request, Plan $plan, \App\Services\GeminiService $geminiService)
    {
        if ($plan->paciente_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'dureza' => 'required|integer|min:1|max:10',
            'dolor' => 'required|string|in:Sin dolor,Molestia ligera,Dolor moderado,Dolor intenso,Incapacitante',
            'evolucion' => 'required|string|in:Muy enérgico,Bien,Normal,Cansado,Agotado',
            'comentario' => 'nullable|string|max:1000',
        ]);

        // AI Analysis
        $analisis = $geminiService->analyzeFeedback(
            $validated['dureza'],
            $validated['dolor'] ?? null,
            $validated['evolucion'] ?? null,
            $validated['comentario'] ?? null
        );

        Feedback::updateOrCreate(
            ['plan_id' => $plan->id],
            [
                ...$validated,
                'analisis_ia' => $analisis
            ]
        );

        return redirect()->route('paciente.plan.show', $plan)
            ->with('success', '¡Feedback enviado correctamente! La IA está analizando tus resultados.');
    }
}
