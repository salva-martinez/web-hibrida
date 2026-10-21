<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeFeedbackJob;
use App\Models\Feedback;
use App\Models\Plan;
use App\Models\PlanDia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function show(Plan $plan)
    {
        // Ensure patient can only view their own plans
        if ($plan->paciente_id !== Auth::id()) {
            abort(403);
        }

        $plan->load(['planDias.planEjercicios.ejercicio.estimulo', 'planDias.feedback']);

        // Navigation: previous and next plans
        $user = Auth::user();
        $planAnterior = Plan::where('paciente_id', $user->id)
            ->where('fecha', '<', $plan->fecha)
            ->orderBy('fecha', 'desc')
            ->first();

        $planSiguiente = Plan::where('paciente_id', $user->id)
            ->where('fecha', '>', $plan->fecha)
            ->orderBy('fecha', 'asc')
            ->first();

        return view('paciente.plan', compact('plan', 'planAnterior', 'planSiguiente'));
    }

    public function storeFeedback(Request $request, PlanDia $planDia)
    {
        // Ensure the plan belongs to the auth user
        if ($planDia->plan->paciente_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'dureza' => 'required|integer|min:1|max:10',
            'dolor' => 'required|string|in:Sin dolor,Molestia ligera,Dolor moderado,Dolor intenso,Incapacitante',
            'evolucion' => 'required|string|in:Muy enérgico,Bien,Normal,Cansado,Agotado',
            'comentario' => 'nullable|string|max:1000',
        ]);

        // Save feedback immediately; clear any stale analysis so it isn't shown
        // while the (possibly slow) AI call runs out of the request cycle.
        // The previous error is cleared too: this is a new submission, so the
        // AI gets a new chance.
        $feedback = Feedback::updateOrCreate(
            ['plan_dia_id' => $planDia->id],
            [
                ...$validated,
                'analisis_ia' => null,
                'analisis_error' => null,
            ]
        );

        // Run the Gemini analysis asynchronously so the patient isn't blocked
        // by retries/timeouts. The job fills analisis_ia when it completes.
        AnalyzeFeedbackJob::dispatch($feedback);

        return redirect()->route('paciente.plan.show', $planDia->plan_id)
            ->with('success', '¡Feedback del Día '.$planDia->numero_dia.' enviado correctamente! La IA lo está analizando.');
    }
}
