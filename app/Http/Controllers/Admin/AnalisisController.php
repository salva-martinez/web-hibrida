<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;

/**
 * Estado del análisis de IA de un feedback.
 *
 * El informe lo genera Gemini en segundo plano (cola), así que el panel del
 * fisioterapeuta consulta aquí periódicamente hasta que está disponible.
 */
class AnalisisController extends Controller
{
    public function show(Feedback $feedback): JsonResponse
    {
        abort_unless($feedback->planDia?->plan?->fisio_id === auth()->id(), 403);

        if ($feedback->analisis_ia) {
            return response()->json([
                'estado' => 'listo',
                'analisis' => $feedback->analisis_ia,
            ]);
        }

        return response()->json([
            'estado' => 'pendiente',
            'analisis' => null,
        ]);
    }
}
