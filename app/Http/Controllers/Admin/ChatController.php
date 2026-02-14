<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, GeminiService $geminiService)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'patient_id' => 'required|exists:users,id',
        ]);

        $paciente = User::findOrFail($request->patient_id);

        // Security check: ensure user is a patient
        if (!$paciente->isPaciente()) {
            return response()->json(['error' => 'El usuario no es un paciente válido.'], 400);
        }

        // Recuperar historial completo
        $historial = $paciente->planesComoPaciente()
            ->with(['feedback', 'planEjercicios.ejercicio'])
            ->orderBy('fecha', 'asc') // Orden cronológico para la IA
            ->get();

        $respuesta = $geminiService->chatWithHistory($historial, $request->message, $paciente->nombre_completo);

        return response()->json([
            'response' => $respuesta
        ]);
    }
}
