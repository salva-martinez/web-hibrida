<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Models\Ejercicio;
use App\Models\PlanEjercicio;
use App\Models\Feedback;
use Carbon\Carbon;

class PlanHistorialSeeder extends Seeder
{
    public function run(): void
    {
        $fisio = User::fisios()->first();
        $ejercicios = Ejercicio::all();

        if ($ejercicios->isEmpty()) {
            $this->command->info('No hay ejercicios. Seeding abortado.');
            return;
        }

        // Obtener todos los pacientes
        $pacientes = User::pacientes()->get();

        foreach ($pacientes as $paciente) {
            $this->command->info("Generando planes para: {$paciente->nombre_completo}");
            
            // Limpiar planes anteriores
            Plan::where('paciente_id', $paciente->id)->delete();

            // Escenario basado en el paciente
            switch ($paciente->nombre) {
                case 'Carlos': // Caso estándar: Evolución positiva
                    $this->crearHistorialCarlos($paciente, $fisio, $ejercicios);
                    break;
                case 'Ana': // Caso dolor: Reporta molestias
                    $this->crearHistorialAna($paciente, $fisio, $ejercicios);
                    break;
                case 'Beto': // Caso estancamiento
                    $this->crearHistorialBeto($paciente, $fisio, $ejercicios);
                    break;
                default:
                    // Genérico para otros
                    $this->crearHistorialGenerico($paciente, $fisio, $ejercicios);
                    break;
            }
        }
    }

    private function crearHistorialCarlos($paciente, $fisio, $ejercicios)
    {
        // Plan 1 (-2 meses)
        $p1 = $this->createPlan($paciente, $fisio, 'Fase 1: Adaptación', -60, false);
        $this->addEjercicios($p1, $ejercicios, 3, 2, '12-15', 5);
        $this->createFeedback($p1, 7, 'Sin dolor', 'Normal', 'Todo bien, agujetas normales.', 
            "El paciente inicia con buena adaptación. RPE 7/10 adecuado para fase 1. Sin dolor reportado. \nRecomendación: Mantener volumen, empezar a subir intensidad.");

        // Plan 2 (-1 mes)
        $p2 = $this->createPlan($paciente, $fisio, 'Fase 2: Fuerza Base', -30, false);
        $this->addEjercicios($p2, $ejercicios, 4, 3, '10-12', 7);
        $this->createFeedback($p2, 8, 'Molestia ligera', 'Bien', 'Un poco de molestia en rodilla al acabar, pero se pasa rápido.', 
            "Aumento de intensidad bien tolerado (RPE 8). La molestia ligera en rodilla es esperable por el aumento de carga. \nRecomendación: Monitorizar rodilla, asegurar descanso entre series.");

        // Plan 3 (Actual)
        $p3 = $this->createPlan($paciente, $fisio, 'Fase 3: Hipertrofia', 0, true);
        $this->addEjercicios($p3, $ejercicios, 5, 4, '8-10', 8);
        // Sin feedback (para que el usuario lo rellene)
    }

    private function crearHistorialAna($paciente, $fisio, $ejercicios)
    {
        // Plan 1 (-3 semanas)
        $p1 = $this->createPlan($paciente, $fisio, 'Inicio Rehabilitación', -21, false);
        $this->addEjercicios($p1, $ejercicios, 3, 2, '15', 4);
        $this->createFeedback($p1, 9, 'Dolor moderado', 'Cansado', 'Me duele bastante al hacer las sentadillas.', 
            "ALERTA: Paciente reporta dolor moderado y RPE alto (9/10) para una carga inicial. \nRecomendación: BAJAR carga inmediatamente. Revisar técnica de sentadilla o sustituir por ejercicio isométrico.");

        // Plan 2 (Actual) - Plan modificado por dolor
        $p2 = $this->createPlan($paciente, $fisio, 'Ajuste: Carga Baja', 0, true);
        $this->addEjercicios($p2, $ejercicios, 3, 2, '10', 3); 
        // Sin feedback
    }

    private function crearHistorialBeto($paciente, $fisio, $ejercicios)
    {
        // Plan 1 (-1 mes)
        $p1 = $this->createPlan($paciente, $fisio, 'Fuerza General', -30, false);
        $this->addEjercicios($p1, $ejercicios, 4, 3, '10', 6);
        $this->createFeedback($p1, 5, 'Sin dolor', 'Normal', 'Muy fácil, no me canso.', 
            "Paciente reporta RPE 5/10 (muy bajo). La carga es insuficiente para generar adaptaciones. \nRecomendación: Aumentar peso o repeticiones significativamente.");
        
        // Plan 2 (Actual)
        $p2 = $this->createPlan($paciente, $fisio, 'Fuerza General II', 0, true);
        $this->addEjercicios($p2, $ejercicios, 4, 3, '10', 8);
    }
    
    private function crearHistorialGenerico($paciente, $fisio, $ejercicios)
    {
        $p1 = $this->createPlan($paciente, $fisio, 'Plan General', 0, true);
        $this->addEjercicios($p1, $ejercicios, 3, 3, '12', 6);
    }

    // Helpers
    private function createPlan($paciente, $fisio, $titulo, $daysOffset, $activo)
    {
        return Plan::create([
            'paciente_id' => $paciente->id,
            'fisio_id' => $fisio->id,
            'titulo' => $titulo,
            'fecha' => Carbon::now()->addDays($daysOffset),
            'activo' => $activo,
        ]);
    }

    private function addEjercicios($plan, $ejercicios, $count, $series, $reps, $intensidad)
    {
        $selected = $ejercicios->shuffle()->take($count);
        $orden = 1;
        foreach ($selected as $ej) {
            PlanEjercicio::create([
                'plan_id' => $plan->id,
                'ejercicio_id' => $ej->id,
                'series' => $series,
                'repeticiones' => $reps,
                'intensidad' => $intensidad,
                'descanso' => '1-2 min',
                'orden' => $orden++,
            ]);
        }
    }

    private function createFeedback($plan, $dureza, $dolor, $evolucion, $comentario, $analisis)
    {
        Feedback::create([
            'plan_id' => $plan->id,
            'dureza' => $dureza,
            'dolor' => $dolor,
            'evolucion' => $evolucion,
            'comentario' => $comentario,
            'analisis_ia' => $analisis,
            'created_at' => $plan->fecha->copy()->addDays(5) // Feedback enviado 5 días después del plan
        ]);
    }
}
