<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function analyzeFeedback($dureza, $dolor, $evolucion, $comentario)
    {
        if (!$this->apiKey) {
            Log::warning('Gemini API Key non configured.');
            return null;
        }

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
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
            } else {
                Log::error('Gemini API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return null;
        }
    }
}
