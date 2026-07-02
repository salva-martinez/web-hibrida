<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanDia;
use App\Models\PlanEjercicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanService
{
    /**
     * Creates a new Plan and its associated PlanDias and PlanEjercicios.
     *
     * @param array $data
     * @return Plan
     */
    public function createPlan(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            // Auto-inactivate previous active plans for this patient if the new one is active
            if ($data['activo']) {
                 Plan::where('paciente_id', $data['paciente_id'])
                    ->where('activo', true)
                    ->update(['activo' => false]);
            }

            $plan = Plan::create([
                'paciente_id' => $data['paciente_id'],
                'fisio_id' => Auth::id(),
                'titulo' => $data['titulo'],
                'fecha' => $data['fecha'],
                'activo' => $data['activo'],
            ]);

            $this->syncDiasAndEjercicios($plan, $data['dias']);

            return $plan;
        });
    }

    /**
     * Updates an existing Plan and its associated PlanDias and PlanEjercicios.
     *
     * @param Plan $plan
     * @param array $data
     * @return Plan
     */
    public function updatePlan(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            // Auto-inactivate other active plans for this patient if this one becomes active
            if ($data['activo']) {
                Plan::where('paciente_id', $data['paciente_id'])
                    ->where('id', '!=', $plan->id)
                    ->where('activo', true)
                    ->update(['activo' => false]);
            }

            $plan->update([
                'paciente_id' => $data['paciente_id'],
                'titulo' => $data['titulo'],
                'fecha' => $data['fecha'],
                'activo' => $data['activo'],
            ]);

            $this->syncDiasAndEjercicios($plan, $data['dias']);

            return $plan;
        });
    }

    /**
     * Synchronizes days and exercises for a given plan.
     *
     * @param Plan $plan
     * @param array $dias
     */
    private function syncDiasAndEjercicios(Plan $plan, array $dias): void
    {
        $numerosDiasEnviados = [];

        foreach ($dias as $numeroDia => $diaData) {
            // Only create the day if it has exercises
            if (!isset($diaData['ejercicios']) || empty($diaData['ejercicios'])) {
                continue;
            }

            $numerosDiasEnviados[] = $numeroDia;

            // Reuse the existing PlanDia (and its feedback) if this day already existed
            $planDia = PlanDia::updateOrCreate(
                ['plan_id' => $plan->id, 'numero_dia' => $numeroDia],
                ['notas_fisio' => $diaData['notas_fisio'] ?? null]
            );

            // Exercises carry no feedback themselves, safe to replace
            $planDia->planEjercicios()->delete();

            foreach ($diaData['ejercicios'] as $index => $ej) {
                PlanEjercicio::create([
                    'plan_dia_id' => $planDia->id,
                    'ejercicio_id' => $ej['ejercicio_id'],
                    'series' => $ej['series'],
                    'repeticiones' => $ej['repeticiones'],
                    'intensidad' => $ej['intensidad'],
                    'kg' => $ej['kg'] ?? null,
                    'descanso' => $ej['descanso'] ?? null,
                    'orden' => $index,
                ]);
            }
        }

        // Remove days that are no longer part of the plan (cascades their feedback too)
        $plan->planDias()->whereNotIn('numero_dia', $numerosDiasEnviados)->delete();
    }
}
