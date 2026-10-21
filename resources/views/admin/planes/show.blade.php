@extends('layouts.app')
@section('title', $plan->titulo)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $plan->titulo }}</h1>
            <p class="subtitle">Paciente: {{ $plan->paciente->nombre_completo }} — {{ $plan->fecha->format('d/m/Y') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.planes.edit', $plan) }}" class="btn btn-primary">Editar</a>
            <a href="{{ route('admin.planes.index') }}" class="btn btn-secondary">← Volver</a>
        </div>
    </div>

    @foreach($plan->planDias as $dia)
        <div style="margin-bottom: 2.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 1.5rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">🗓️ Día {{ $dia->numero_dia }}</h2>
            @if($dia->notas_fisio)
                <p style="background: rgba(14, 165, 233, 0.1); padding: 1rem; border-left: 4px solid var(--primary); border-radius: var(--radius-sm); color: var(--text-primary); margin-bottom: 1.5rem;">
                    <strong>💬 Notas del fisioterapeuta:</strong> {{ $dia->notas_fisio }}
                </p>
            @endif

            {{-- Exercise Table (Excel style) --}}
            <div class="card" style="margin-bottom: 1.5rem">
                <div class="card-body" style="padding: 0; overflow-x: auto;">
                    <x-ejercicios-tabla :dia="$dia" />
                </div>
            </div>

            {{-- Feedback --}}
            @if($dia->feedback)
                <div class="card">
                    <div class="card-header">
                        <h3>📩 Feedback del Día {{ $dia->numero_dia }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="feedback-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <div class="feedback-user">
                                <h4>📝 Datos del Paciente</h4>
                                <p><strong>Dureza del entreno:</strong> <span
                                        class="dureza-badge">{{ $dia->feedback->dureza }}/10</span></p>

                                @if($dia->feedback->dolor)
                                    <div style="margin-top: 1rem">
                                        <p><strong>🤕 Zonas de Dolor:</strong></p>
                                        <p style="color: var(--text-secondary)">{{ $dia->feedback->dolor }}</p>
                                    </div>
                                @endif

                                @if($dia->feedback->evolucion)
                                    <div style="margin-top: 1rem">
                                        <p><strong>📈 Evolución:</strong></p>
                                        <p style="color: var(--text-secondary)">{{ $dia->feedback->evolucion }}</p>
                                    </div>
                                @endif

                                @if($dia->feedback->comentario)
                                    <div style="margin-top: 1rem">
                                        <p><strong>💬 Comentario Adicional:</strong></p>
                                        <p style="color: var(--text-secondary)">{{ $dia->feedback->comentario }}</p>
                                    </div>
                                @endif

                                <p style="margin-top: 1rem; color: var(--text-muted); font-size: 0.8rem">
                                    Enviado el {{ $dia->feedback->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>

                            <div class="feedback-ai"
                                style="background: rgba(var(--primary-rgb), 0.05); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(var(--primary-rgb), 0.1);">
                                <h4 style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary-color);">
                                    🤖 Análisis IA (Día {{ $dia->numero_dia }})
                                </h4>
                                @if($dia->feedback->analisis_ia)
                                    <div style="margin-top: 1rem; line-height: 1.6; white-space: pre-line;">
                                        {{ $dia->feedback->analisis_ia }}
                                    </div>
                                @else
                                    <div
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; color: var(--text-muted);">
                                        <x-feedback-ai-status :feedback="$dia->feedback" />
                                        <p style="margin-top: .5rem">La IA está redactando el informe. Aparecerá solo, sin recargar.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state" style="padding: 1.5rem; text-align: center;">
                            <div class="icon">💬</div>
                            <h4 style="margin: 0.5rem 0;">Sin feedback para el Día {{ $dia->numero_dia }}</h4>
                            <p style="color: #6b7280; margin: 0;">El paciente todavía no ha completado este día.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach


    {{-- AI Clinical Assistant Chat --}}
    <div class="card" style="margin-top: 1.5rem">
        <div class="card-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                <span>🤖</span> Asistente Clínico IA (Historial Completo)
            </h3>
        </div>
        <div class="card-body" id="chat-ia"
            data-chat-url="{{ route('admin.chat.send') }}"
            data-patient-id="{{ $plan->paciente->id }}">
            <div id="chat-messages"
                style="height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; background: #f9fafb; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.8rem;">
                <div class="message ai"
                    style="align-self: flex-start; background: white; color: #333; padding: 0.8rem 1rem; border-radius: 8px 8px 8px 0; border: 1px solid #e5e7eb; max-width: 80%; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    Hola. Tengo acceso a todo el historial de ejercicios y feedback de
                    <strong>{{ $plan->paciente->nombre_completo }}</strong>. ¿En qué puedo ayudarte hoy?
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <input type="text" id="chat-input" class="form-control"
                    placeholder="Ej: ¿Cómo ha evolucionado su dolor de rodilla este mes?" style="flex: 1;">
                <button type="button" id="chat-send" class="btn btn-primary"
                    style="display: flex; align-items: center; gap: 0.5rem;">
                    Enviar <span>➤</span>
                </button>
            </div>
        </div>
    </div>
@endsection