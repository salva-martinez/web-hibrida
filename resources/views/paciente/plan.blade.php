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

    {{-- Day Tabs --}}
    <div class="tabs-container" style="display: flex; gap: 0.5rem; overflow-x: auto; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">
        @foreach($plan->planDias as $index => $dia)
            <button class="btn btn-sm {{ $index === 0 ? 'btn-primary' : 'btn-outline-primary' }}" 
                    onclick="switchDayTab({{ $dia->numero_dia }})" 
                    id="tab-btn-{{ $dia->numero_dia }}">
                Día {{ $dia->numero_dia }}
            </button>
        @endforeach
    </div>

    @foreach($plan->planDias as $index => $dia)
        <div id="day-content-{{ $dia->numero_dia }}" style="display: {{ $index === 0 ? 'block' : 'none' }};">
            @if($dia->notas_fisio)
                <div style="background: rgba(14, 165, 233, 0.1); padding: 1rem; border-left: 4px solid var(--primary); border-radius: var(--radius-sm); color: var(--text-primary); margin-bottom: 1.5rem;">
                    <p style="margin: 0;"><strong>💬 Nota de tu fisio:</strong> {{ $dia->notas_fisio }}</p>
                </div>
            @endif

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
                            @foreach($dia->ejerciciosPorEstimulo() as $estimuloNombre => $ejercicios)
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
                                            @if($pe->ejercicio->tieneVideo())
                                                <a href="javascript:void(0)" class="video-link"
                                                    onclick="openVideo({{ Js::from($pe->ejercicio->embed_url) }}, {{ Js::from($pe->ejercicio->nombre) }})">
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

            {{-- Feedback Section --}}
            <div class="feedback-section">
                <h3>📝 ¿Cómo ha ido el Día {{ $dia->numero_dia }}?</h3>

                @if($dia->feedback)
                    <div class="feedback-existing" style="margin-bottom: 1rem">
                        <p><strong>Tu valoración anterior:</strong></p>
                        <p>Dureza: <span class="dureza-badge">{{ $dia->feedback->dureza }}/10</span></p>
                        @if($dia->feedback->comentario)
                            <p style="margin-top: 0.5rem; color: var(--text-secondary)">{{ $dia->feedback->comentario }}</p>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('paciente.plan.feedback', $dia) }}" class="feedbackForm">
                    @csrf

                    <div class="form-group">
                        <label>Puntúa la dureza del entreno</label>
                        <div class="slider-value" id="dureza-display-{{ $dia->numero_dia }}">{{ $dia->feedback->dureza ?? 5 }}</div>
                        <div class="slider-container">
                            <input type="range" name="dureza" min="1" max="10"
                                value="{{ $dia->feedback->dureza ?? 5 }}"
                                oninput="document.getElementById('dureza-display-{{ $dia->numero_dia }}').textContent = this.value">
                            <div class="slider-label">
                                <span>1 — Muy fácil</span>
                                <span>10 — Muy duro</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="dolor-{{ $dia->numero_dia }}">¿Has sentido dolor o molestias?</label>
                        <select name="dolor" id="dolor-{{ $dia->numero_dia }}" class="form-control">
                            <option value="Sin dolor" {{ ($dia->feedback->dolor ?? '') == 'Sin dolor' ? 'selected' : '' }}>Sin dolor</option>
                            <option value="Molestia ligera" {{ ($dia->feedback->dolor ?? '') == 'Molestia ligera' ? 'selected' : '' }}>Molestia ligera</option>
                            <option value="Dolor moderado" {{ ($dia->feedback->dolor ?? '') == 'Dolor moderado' ? 'selected' : '' }}>Dolor moderado</option>
                            <option value="Dolor intenso" {{ ($dia->feedback->dolor ?? '') == 'Dolor intenso' ? 'selected' : '' }}>Dolor intenso</option>
                            <option value="Incapacitante" {{ ($dia->feedback->dolor ?? '') == 'Incapacitante' ? 'selected' : '' }}>Incapacitante</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="evolucion-{{ $dia->numero_dia }}">¿Cómo has notado tu evolución?</label>
                        <select name="evolucion" id="evolucion-{{ $dia->numero_dia }}" class="form-control">
                            <option value="Muy enérgico" {{ ($dia->feedback->evolucion ?? '') == 'Muy enérgico' ? 'selected' : '' }}>Muy enérgico</option>
                            <option value="Bien" {{ ($dia->feedback->evolucion ?? '') == 'Bien' ? 'selected' : '' }}>Bien</option>
                            <option value="Normal" {{ ($dia->feedback->evolucion ?? '') == 'Normal' ? 'selected' : '' }}>Normal</option>
                            <option value="Cansado" {{ ($dia->feedback->evolucion ?? '') == 'Cansado' ? 'selected' : '' }}>Cansado</option>
                            <option value="Agotado" {{ ($dia->feedback->evolucion ?? '') == 'Agotado' ? 'selected' : '' }}>Agotado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comentario-{{ $dia->numero_dia }}">¿Algún comentario adicional?</label>
                        <textarea name="comentario" id="comentario-{{ $dia->numero_dia }}" class="form-control" rows="3"
                            placeholder="Cuéntale a tu fisio cómo te has sentido...">{{ $dia->feedback->comentario ?? '' }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-success submitBtn">{{ $dia->feedback ? 'Actualizar Feedback' : 'Enviar Feedback del Día' }}</button>
                </form>
            </div>
        </div>
    @endforeach

    {{-- Video Modal --}}
    <div class="video-modal-overlay" id="videoModal" onclick="closeVideo(event)">
        <div class="video-modal">
            <div class="video-modal-header">
                <h3 id="videoTitle">Vídeo del ejercicio</h3>
                <button class="video-modal-close" onclick="closeVideo()">✕</button>
            </div>
            <div class="video-modal-body">
                <iframe id="videoFrame" src="" title="Vídeo del ejercicio"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openVideo(url, title) {
                if (!url) return;
                var frame = document.getElementById('videoFrame');
                var titleEl = document.getElementById('videoTitle');
                var modal = document.getElementById('videoModal');

                if (frame) frame.src = url;
                if (titleEl) titleEl.textContent = title;
                if (modal) modal.classList.add('active');
            }

            function closeVideo(event) {
                if (event && event.target !== event.currentTarget) return;

                var frame = document.getElementById('videoFrame');
                var modal = document.getElementById('videoModal');

                if (frame) frame.src = '';
                if (modal) modal.classList.remove('active');
            }

            function switchDayTab(dayNum) {
                // Hide all contents and deactivate all buttons
                document.querySelectorAll('[id^="day-content-"]').forEach(el => el.style.display = 'none');
                document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                });

                // Show selected content and activate button
                const content = document.getElementById(`day-content-${dayNum}`);
                const btn = document.getElementById(`tab-btn-${dayNum}`);
                
                if (content) content.style.display = 'block';
                if (btn) {
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const feedbackForms = document.querySelectorAll('.feedbackForm');
                feedbackForms.forEach(form => {
                    form.addEventListener('submit', function (e) {
                        const btn = form.querySelector('.submitBtn');
                        if (btn) {
                            btn.disabled = true;
                            btn.innerHTML = '⏳ Procesando feedback con IA...';
                            btn.style.opacity = '0.7';
                            btn.style.cursor = 'wait';
                        }
                    });
                });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeVideo();
            });
        </script>
    @endpush
@endsection