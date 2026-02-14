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

    {{-- Exercise Table (Excel style) --}}
    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-body" style="padding: 0; overflow-x: auto;">
            <table class="exercise-table">
                <thead>
                    <tr>
                        <th>Estímulo</th>
                        <th>Ejercicio</th>
                        <th>Series</th>
                        <th>Reps</th>
                        <th>RPE</th>
                        <th>KG</th>
                        <th>Descanso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ejerciciosPorEstimulo as $estimuloNombre => $ejercicios)
                        @php
                            $class = 'estimulo-basico';
                            $lower = strtolower($estimuloNombre);
                            if (str_contains($lower, 'calentamiento'))
                                $class = 'estimulo-calentamiento';
                            elseif (str_contains($lower, 'auxiliar'))
                                $class = 'estimulo-auxiliar';
                            elseif (str_contains($lower, 'metab') || str_contains($lower, 'elíptica'))
                                $class = 'estimulo-metabolico';
                        @endphp
                        <tr>
                            <td class="estimulo-header {{ $class }}" rowspan="{{ $ejercicios->count() + 1 }}">
                                {{ $estimuloNombre }}
                            </td>
                        </tr>
                        @foreach($ejercicios as $pe)
                            <tr>
                                <td class="ejercicio-nombre">
                                    @if($pe->ejercicio->video_url)
                                        <a href="{{ $pe->ejercicio->video_url }}" target="_blank" class="video-link"
                                            style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;">
                                            <span>▶️</span> {{ $pe->ejercicio->nombre }}
                                        </a>
                                    @else
                                        {{ $pe->ejercicio->nombre }}
                                    @endif
                                </td>
                                <td>{{ $pe->series }}</td>
                                <td>{{ $pe->repeticiones }}</td>
                                <td>{{ $pe->intensidad ?: '—' }}</td>
                                <td>{{ $pe->kg ? number_format($pe->kg, 1) : '—' }}</td>
                                <td>{{ $pe->descanso ?: '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Feedback --}}
    @if($plan->feedback)
        <div class="card">
            <div class="card-header">
                <h3>📩 Feedback del Paciente</h3>
            </div>
            <div class="card-body">
                <div class="feedback-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="feedback-user">
                        <h4>📝 Datos del Paciente</h4>
                        <p><strong>Dureza del entreno:</strong> <span
                                class="dureza-badge">{{ $plan->feedback->dureza }}/10</span></p>

                        @if($plan->feedback->dolor)
                            <div style="margin-top: 1rem">
                                <p><strong>🤕 Zonas de Dolor:</strong></p>
                                <p style="color: var(--text-secondary)">{{ $plan->feedback->dolor }}</p>
                            </div>
                        @endif

                        @if($plan->feedback->evolucion)
                            <div style="margin-top: 1rem">
                                <p><strong>📈 Evolución Semanal:</strong></p>
                                <p style="color: var(--text-secondary)">{{ $plan->feedback->evolucion }}</p>
                            </div>
                        @endif

                        @if($plan->feedback->comentario)
                            <div style="margin-top: 1rem">
                                <p><strong>💬 Comentario Adicional:</strong></p>
                                <p style="color: var(--text-secondary)">{{ $plan->feedback->comentario }}</p>
                            </div>
                        @endif

                        <p style="margin-top: 1rem; color: var(--text-muted); font-size: 0.8rem">
                            Enviado el {{ $plan->feedback->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <div class="feedback-ai"
                        style="background: rgba(var(--primary-rgb), 0.05); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(var(--primary-rgb), 0.1);">
                        <h4 style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary-color);">
                            🤖 Análisis del Asistente de IA
                        </h4>
                        @if($plan->feedback->analisis_ia)
                            <div style="margin-top: 1rem; line-height: 1.6; white-space: pre-line;">
                                {{ $plan->feedback->analisis_ia }}
                            </div>
                        @else
                            <div
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; color: var(--text-muted);">
                                <p>Análisis pendiente o no disponible.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="icon">💬</div>
                    <h3>Sin feedback aún</h3>
                    <p>El paciente todavía no ha enviado su valoración de este plan.</p>
                </div>
            </div>
        </div>
    @endif


    {{-- AI Clinical Assistant Chat --}}
    <div class="card" style="margin-top: 1.5rem">
        <div class="card-header" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
                <span>🤖</span> Asistente Clínico IA (Historial Completo)
            </h3>
        </div>
        <div class="card-body">
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatInput = document.getElementById('chat-input');
            const chatSendStr = document.getElementById('chat-send');
            const chatMessages = document.getElementById('chat-messages');
            const patientId = {{ $plan->paciente->id }};

            function appendMessage(text, sender) {
                const div = document.createElement('div');
                div.className = `message ${sender}`;
                div.style.cssText = sender === 'user'
                    ? 'align-self: flex-end; background: #4f46e5; color: white; padding: 0.8rem 1rem; border-radius: 8px 8px 0 8px; max-width: 80%; box-shadow: 0 1px 2px rgba(0,0,0,0.1);'
                    : 'align-self: flex-start; background: white; color: #1f2937; padding: 0.8rem 1rem; border-radius: 8px 8px 8px 0; border: 1px solid #e5e7eb; max-width: 80%; box-shadow: 0 1px 2px rgba(0,0,0,0.05);';
                div.innerText = text;
                chatMessages.appendChild(div);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            async function sendMessage() {
                const message = chatInput.value.trim();
                if (!message) return;

                // Add user message
                appendMessage(message, 'user');
                chatInput.value = '';
                chatInput.disabled = true;
                chatSendStr.disabled = true;
                chatSendStr.innerHTML = 'Pensando...';

                try {
                    const response = await fetch("{{ route('admin.chat.send') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            message: message,
                            patient_id: patientId
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        appendMessage(data.response, 'ai');
                    } else {
                        appendMessage('Error: ' + (data.error || 'No se pudo conectar con la IA'), 'ai');
                    }
                } catch (error) {
                    appendMessage('Error de conexión.', 'ai');
                    console.error(error);
                } finally {
                    chatInput.disabled = false;
                    chatSendStr.disabled = false;
                    chatSendStr.innerHTML = 'Enviar <span>➤</span>';
                    chatInput.focus();
                }
            }

            chatSendStr.addEventListener('click', sendMessage);
            chatInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') sendMessage();
            });
        });
    </script>
@endsection