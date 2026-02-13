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
                                        <a href="{{ $pe->ejercicio->video_url }}" target="_blank"
                                            class="video-link">{{ $pe->ejercicio->nombre }}</a>
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
@endsection