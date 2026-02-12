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
        // 1. Get our patient Carlos
        $paciente = User::where('nombre', 'Carlos')
            ->where('apellido1', 'García')
            ->first();

        if (!$paciente) {
            $this->command->info('Paciente Carlos no encontrado. Seeding abortado.');
            return;
        }

        // CLEAR existing plans for this patient to ensure clean history
        Plan::where('paciente_id', $paciente->id)->delete();

        $fisio = User::fisios()->first();
        $ejercicios = Ejercicio::all();

        if ($ejercicios->isEmpty()) {
            $this->command->info('No hay ejercicios. Seeding abortado.');
            return;
        }

        // --- Plan 1: Inicio (Hace 1 mes) ---
        $plan1 = Plan::create([
            'paciente_id' => $paciente->id,
            'fisio_id' => $fisio->id ?? 1,
            'titulo' => 'Fase 1: Adaptación y Control Motor',
            'fecha' => Carbon::now()->subMonths(1),
            'activo' => false, // Ya no es el actual
        ]);
        $this->addEjercicios($plan1, $ejercicios, 3, 2, '12-15', 4);
        Feedback::create([
            'plan_id' => $plan1->id,
            'dureza' => 8,
            'comentario' => 'Me costó un poco al principio, sobre todo la coordinación. Pero bien.',
            'created_at' => Carbon::now()->subWeeks(3)
        ]);

        // --- Plan 2: Intermedio (Hace 2 semanas) ---
        $plan2 = Plan::create([
            'paciente_id' => $paciente->id,
            'fisio_id' => $fisio->id ?? 1,
            'titulo' => 'Fase 2: Fuerza Base',
            'fecha' => Carbon::now()->subWeeks(2),
            'activo' => false,
        ]);
        $this->addEjercicios($plan2, $ejercicios, 4, 3, '10', 6, 5);
        Feedback::create([
            'plan_id' => $plan2->id,
            'dureza' => 6,
            'comentario' => 'Me sentí más fuerte. Listo para subir peso.',
            'created_at' => Carbon::now()->subWeek()
        ]);

        // --- Plan 3: Avanzado (Actual) ---
        $plan3 = Plan::create([
            'paciente_id' => $paciente->id,
            'fisio_id' => $fisio->id ?? 1,
            'titulo' => 'Fase 3: Hipertrofia y Potencia',
            'fecha' => Carbon::now(),
            'activo' => true, // Activo
        ]);
        $this->addEjercicios($plan3, $ejercicios, 5, 4, '8-10', 8, 10);
        
        $this->command->info('Historial de 3 planes generado para Carlos García.');
    }

    private function addEjercicios($plan, $ejercicios, $count, $series, $reps, $intensidad, $kg = null)
    {
        // Shuffle and take some exercises
        $selected = $ejercicios->shuffle()->take($count);
        $orden = 1;
        
        foreach ($selected as $ej) {
            PlanEjercicio::create([
                'plan_id' => $plan->id,
                'ejercicio_id' => $ej->id,
                'series' => $series,
                'repeticiones' => $reps,
                'intensidad' => $intensidad,
                'kg' => $kg,
                'descanso' => '2 min',
                'orden' => $orden++,
            ]);
        }
    }
}
