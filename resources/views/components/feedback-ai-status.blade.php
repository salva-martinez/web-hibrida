{{--
    Estado del análisis de IA de un feedback.

    Si el informe todavía no está, el elemento marca "pendiente" y el módulo
    resources/js/ai-status.js lo consulta periódicamente hasta que aparezca.

    @param \App\Models\Feedback $feedback
--}}
@props(['feedback'])

@if($feedback->analisis_ia)
    <span class="badge badge-success">🤖 Análisis disponible</span>
@else
    <span class="badge badge-warning"
        data-analisis-url="{{ route('admin.feedback.analisis', $feedback->id) }}"
        data-estado="pendiente">⏳ Analizando…</span>
@endif
