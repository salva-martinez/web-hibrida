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

        // Solo se puede consultar el historial de un paciente propio: scopePacientes()
        // filtra por propietario y findOrFail devuelve 404 si no es tuyo.
        $paciente = User::pacientes()->findOrFail($request->patient_id);

        // Recuperar historial completo
        $historial = $paciente->planesComoPaciente()
            ->with(['planDias.feedback', 'planDias.planEjercicios.ejercicio'])
            ->orderBy('fecha', 'asc') // Orden cronológico para la IA
            ->get();

        $respuesta = $geminiService->chatWithHistory($historial, $request->message, $paciente->nombre_completo);

        return response()->json([
            'response' => $respuesta
        ]);
    }
}
