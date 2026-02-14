<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function analyzeFeedback($dureza, $dolor, $evolucion, $comentario)
    {
        if (!$this->apiKey) {
            Log::error('GeminiService: API Key is missing in .env (GEMINI_API_KEY)');
            return null;
        }

        Log::info('GeminiService: Sending prompt to API...');

        $prompt = "Actúa como un fisioterapeuta experto y redacta un BREVE informe clínico (máximo 100 palabras) en base al siguiente feedback de un paciente tras su sesión de ejercicios.\n\n" .
            "Datos del paciente:\n" .
            "- Dureza percibida (RPE): {$dureza}/10\n" .
            "- Zona de dolor: " . ($dolor ?: 'Sin dolor') . "\n" .
            "- Evolución o sensación semanal: " . ($evolucion ?: 'Normal') . "\n" .
            "- Comentario adicional: " . ($comentario ?: 'Ninguno') . "\n\n" .
            "Estructura del informe:\n" .
            "1. Análisis de carga y síntomas (considera si hay dolor o fatiga acumulada).\n" .
            "2. Recomendación para la próxima sesión (subir carga, mantener, bajar o descansar).";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->retry(3, 2000) // 3 retries, 2 seconds apart
              ->timeout(60)     // Increased timeout
              ->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
                Log::info('GeminiService: API Response received', ['text_preview' => substr($text ?? '', 0, 50) . '...']);
                return $text;
            } else {
                Log::error('GeminiService: API Error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return null;
        }
    }
    public function chatWithHistory($historialPlanes, $mensajeFisio, $nombrePaciente)
    {
        if (!$this->apiKey) {
            Log::error('GeminiService: API Key is missing in .env (GEMINI_API_KEY)');
            return null;
        }

        // Construir contexto histórico
        $contexto = "Estás analizando el historial del paciente {$nombrePaciente}. Aquí tienes sus registros anteriores:\n\n";

        foreach ($historialPlanes as $plan) {
            $contexto .= "--- PLAN: {$plan->titulo} ({$plan->fecha->format('d/m/Y')}) ---\n";
            if ($plan->feedback) {
                $contexto .= "RPE: {$plan->feedback->dureza}/10\n";
                $contexto .= "Dolor: " . ($plan->feedback->dolor ?: 'Ninguno') . "\n";
                $contexto .= "Evolución: " . ($plan->feedback->evolucion ?: 'No registrada') . "\n";
                $contexto .= "Comentarios: " . ($plan->feedback->comentario ?: 'Sin comentarios') . "\n";
                if ($plan->feedback->analisis_ia) {
                    $contexto .= "Resumen IA previo: {$plan->feedback->analisis_ia}\n";
                }
            } else {
                $contexto .= "Sin feedback registrado.\n";
            }
            $contexto .= "\n";
        }

        $prompt = "Actúa como un asistente clínico experto en fisioterapia. Tienes acceso al historial completo del paciente arriba.\n" .
            "El fisioterapeuta te pregunta: \"{$mensajeFisio}\"\n\n" .
            "Responde de forma concisa, profesional y basada en los datos del historial proporcionado y tu conocimiento como fisioterapeuta. " .
            "Si detectas tendencias (dolor creciente, estancamiento, etc.) menciónalas.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->retry(3, 2000)
              ->timeout(60)
              ->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $contexto . $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
                return $text;
            } else {
                Log::error('GeminiService: Chat API Error', ['status' => $response->status(), 'body' => $response->body()]);
                return "Lo siento, hubo un error al consultar a la IA. Código: " . $response->status();
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return "Error de conexión con el servicio de IA.";
        }
    }
}
