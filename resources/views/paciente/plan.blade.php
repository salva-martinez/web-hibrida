@extends('layouts.app')
@section('title', $plan->titulo)

@section('content')
    {{-- Plan Navigation --}}
    <div class="plan-nav">
        @if($planAnterior)
            <a href="{{ route('paciente.plan.show', $planAnterior) }}" class="btn-nav">
                ← {{ $planAnterior->titulo }}
            </a>
        @else
            <span class="btn-nav disabled">← Anterior</span>
        @endif

        <div class="plan-nav-title">
            <h2>{{ $plan->titulo }}</h2>
            <span class="plan-date">📅 {{ $plan->fecha->format('d/m/Y') }}</span>
        </div>

        @if($planSiguiente)
            <a href="{{ route('paciente.plan.show', $planSiguiente) }}" class="btn-nav">
                {{ $planSiguiente->titulo }} →
            </a>
        @else
            <span class="btn-nav disabled">Siguiente →</span>
        @endif
    </div>

    {{-- Exercise Table (Excel style) --}}
    <div class="card" style="margin-bottom: 1.5rem;">
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
                                        <a href="#" class="video-link"
                                            onclick="openVideo('{{ $pe->ejercicio->embed_url }}', '{{ $pe->ejercicio->nombre }}'); return false;">
                                            🎬 {{ $pe->ejercicio->nombre }}
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

    {{-- Feedback Section --}}
    <div class="feedback-section">
        <h3>📝 ¿Cómo ha ido el entreno?</h3>

        @if($plan->feedback)
            <div class="feedback-existing" style="margin-bottom: 1rem">
                <p><strong>Tu valoración anterior:</strong></p>
                <p>Dureza: <span class="dureza-badge">{{ $plan->feedback->dureza }}/10</span></p>
                @if($plan->feedback->comentario)
                    <p style="margin-top: 0.5rem; color: var(--text-secondary)">{{ $plan->feedback->comentario }}</p>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('paciente.plan.feedback', $plan) }}">
            @csrf

            <div class="form-group">
                <label>Puntúa la dureza del entreno</label>
                <div class="slider-value" id="dureza-display">{{ $plan->feedback->dureza ?? 5 }}</div>
                <div class="slider-container">
                    <input type="range" name="dureza" id="dureza" min="1" max="10"
                        value="{{ $plan->feedback->dureza ?? 5 }}"
                        oninput="document.getElementById('dureza-display').textContent = this.value">
                    <div class="slider-label">
                        <span>1 — Muy fácil</span>
                        <span>10 — Muy duro</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="dolor">¿Has sentido dolor o molestias?</label>
                <select name="dolor" id="dolor" class="form-control">
                    <option value="Sin dolor" {{ ($plan->feedback->dolor ?? '') == 'Sin dolor' ? 'selected' : '' }}>Sin dolor
                    </option>
                    <option value="Molestia ligera" {{ ($plan->feedback->dolor ?? '') == 'Molestia ligera' ? 'selected' : '' }}>Molestia ligera</option>
                    <option value="Dolor moderado" {{ ($plan->feedback->dolor ?? '') == 'Dolor moderado' ? 'selected' : '' }}>
                        Dolor moderado</option>
                    <option value="Dolor intenso" {{ ($plan->feedback->dolor ?? '') == 'Dolor intenso' ? 'selected' : '' }}>
                        Dolor intenso</option>
                    <option value="Incapacitante" {{ ($plan->feedback->dolor ?? '') == 'Incapacitante' ? 'selected' : '' }}>
                        Incapacitante</option>
                </select>
            </div>

            <div class="form-group">
                <label for="evolucion">¿Cómo has notado tu evolución esta semana?</label>
                <select name="evolucion" id="evolucion" class="form-control">
                    <option value="Muy enérgico" {{ ($plan->feedback->evolucion ?? '') == 'Muy enérgico' ? 'selected' : '' }}>
                        Muy enérgico</option>
                    <option value="Bien" {{ ($plan->feedback->evolucion ?? '') == 'Bien' ? 'selected' : '' }}>Bien</option>
                    <option value="Normal" {{ ($plan->feedback->evolucion ?? '') == 'Normal' ? 'selected' : '' }}>Normal
                    </option>
                    <option value="Cansado" {{ ($plan->feedback->evolucion ?? '') == 'Cansado' ? 'selected' : '' }}>Cansado
                    </option>
                    <option value="Agotado" {{ ($plan->feedback->evolucion ?? '') == 'Agotado' ? 'selected' : '' }}>Agotado
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="comentario">¿Algún comentario respecto a este día?</label>
                <textarea name="comentario" id="comentario" class="form-control" rows="3"
                    placeholder="Cuéntale a tu fisio cómo te has sentido...">{{ $plan->feedback->comentario ?? '' }}</textarea>
            </div>

            <button type="submit"
                class="btn btn-success">{{ $plan->feedback ? 'Actualizar Feedback' : 'Enviar Feedback' }}</button>
        </form>
    </div>

    {{-- Video Modal --}}
    <div class="video-modal-overlay" id="videoModal" onclick="closeVideo(event)">
        <div class="video-modal">
            <div class="video-modal-header">
                <h3 id="videoTitle">Vídeo del ejercicio</h3>
                <button class="video-modal-close" onclick="closeVideo()">✕</button>
            </div>
            <div class="video-modal-body">
                <iframe id="videoFrame" src="" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
                    function openVideo(url, title) {
                        document.getElementById('videoFrame').src = url;
                        document.getElementById('videoTitle').textContent = title;
                        document.getElementById('videoModal').classList.add('active');
                    }

                    function closeVideo(event) {
                        if (event && event.target !== event.currentTarget) return;
                        document.getElementById('videoFrame').src = '';
                        document.getEleme ntById('videoModal').classList.remove('active');
                    }   document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') closeVideo();
                    });
        </script>
    @endpush
@endsection