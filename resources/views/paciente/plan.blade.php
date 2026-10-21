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
                    data-day-tab="{{ $dia->numero_dia }}" 
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
                        <x-ejercicios-tabla :dia="$dia" :modal="true" />
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
                                data-dureza-display="dureza-display-{{ $dia->numero_dia }}">
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
                        @error('dolor')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
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
                        @error('evolucion')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="comentario-{{ $dia->numero_dia }}">¿Algún comentario adicional?</label>
                        <textarea name="comentario" id="comentario-{{ $dia->numero_dia }}" class="form-control" rows="3"
                            placeholder="Cuéntale a tu fisio cómo te has sentido...">{{ $dia->feedback->comentario ?? '' }}</textarea>
                        @error('comentario')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success submitBtn">{{ $dia->feedback ? 'Actualizar Feedback' : 'Enviar Feedback del Día' }}</button>
                </form>
            </div>
        </div>
    @endforeach

    {{-- Video Modal --}}
    <div class="video-modal-overlay" id="videoModal">
        <div class="video-modal">
            <div class="video-modal-header">
                <h3 id="videoTitle">Vídeo del ejercicio</h3>
                <button class="video-modal-close" type="button" data-video-close>✕</button>
            </div>
            <div class="video-modal-body">
                <iframe id="videoFrame" src="" title="Vídeo del ejercicio"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection