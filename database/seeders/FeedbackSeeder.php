<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are plans to attach feedback to
        $plan = Plan::latest()->first();

        if (!$plan) {
            $this->command->warn('No plans found. Please run PlanSeeder first.');
            return;
        }

        // 1. Feedback positivo (buena evolución)
        Feedback::updateOrCreate(
            ['plan_id' => $plan->id],
            [
                'dureza' => 7,
                'dolor' => 'Sin dolor',
                'evolucion' => 'Muy enérgico',
                'comentario' => 'Me he sentido muy bien, he podido completar todas las series.',
                'analisis_ia' => "Análisis de carga: El paciente reporta una adaptación positiva a la carga (RPE 7/10) sin dolor asociado y con altos niveles de energía. La respuesta fisiológica es adecuada.\n\nRecomendación: Aumentar ligeramente la intensidad o el volumen en la próxima sesión (principio de sobrecarga progresiva) para mantener el estímulo de entrenamiento."
            ]
        );

        $this->command->info('Feedback de prueba generado para el último plan.');
    }
}
