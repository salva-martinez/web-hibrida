<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;

/**
 * Estado del análisis de IA de un feedback.
 *
 * El informe lo genera Gemini en segundo plano (cola), así que el panel del
 * fisioterapeuta consulta aquí periódicamente hasta que está disponible.
 *
 * Si la IA no ha podido generar el informe (cuota agotada, clave inválida),
 * el motivo queda guardado en el feedback y aquí se traduce a un mensaje
 * legible: el panel tiene que explicar por qué no hay informe, no dejar un
 * "Pendiente" para siempre.
 */
class AnalisisController extends Controller
{
    public function show(Feedback $feedback, GeminiService $geminiService): JsonResponse
    {
        abort_unless($feedback->planDia?->plan?->fisio_id === auth()->id(), 403);

        if ($feedback->analisis_ia) {
            return response()->json([
                'estado' => 'listo',
                'analisis' => $feedback->analisis_ia,
            ]);
        }

        if ($feedback->analisis_error) {
            return response()->json([
                'estado' => 'error',
                'analisis' => null,
                'mensaje' => $geminiService->mensajeDeError($feedback->analisis_error),
            ]);
        }

        return response()->json([
            'estado' => 'pendiente',
            'analisis' => null,
        ]);
    }
}
