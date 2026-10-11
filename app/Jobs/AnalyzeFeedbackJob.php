<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying.
     */
    public int $backoff = 15;

    public function __construct(public Feedback $feedback)
    {
    }

    public function handle(GeminiService $geminiService): void
    {
        $analisis = $geminiService->analyzeFeedback(
            $this->feedback->dureza,
            $this->feedback->dolor,
            $this->feedback->evolucion,
            $this->feedback->comentario,
        );

        // GeminiService swallows API/connection errors and returns null.
        // Throw so the queue retries transient failures; give up after $tries.
        if ($analisis === null) {
            Log::warning('AnalyzeFeedbackJob: null analysis for Feedback ' . $this->feedback->id);
            throw new \RuntimeException('Gemini returned no analysis for Feedback ' . $this->feedback->id);
        }

        $this->feedback->update(['analisis_ia' => $analisis]);
    }
}
