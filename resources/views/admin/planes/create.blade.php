@extends('layouts.app')
@section('title', isset($plan) ? 'Editar Plan' : 'Nuevo Plan')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ isset($plan) ? 'Editar Plan' : 'Nuevo Plan de Ejercicios' }}</h1>
        </div>
        <a href="{{ route('admin.planes.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <form method="POST" action="{{ isset($plan) ? route('admin.planes.update', $plan) : route('admin.planes.store') }}"
        id="planForm">
        @csrf
        @if(isset($plan)) @method('PUT') @endif

        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="paciente_id">Paciente *</label>
                        <select name="paciente_id" id="paciente_id" class="form-control" required>
                            <option value="">— Selecciona paciente —</option>
                            @foreach($pacientes as $pac)
                                <option value="{{ $pac->id }}" {{ old('paciente_id', $plan->paciente_id ?? '') == $pac->id ? 'selected' : '' }}>
                                    {{ $pac->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="titulo">Título del Plan *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control"
                            value="{{ old('titulo', $plan->titulo ?? '') }}" placeholder="Ej: Semana 1 - Plan Inicial"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha *</label>
                        <input type="date" name="fecha" id="fecha" class="form-control"
                            value="{{ old('fecha', isset($plan) ? $plan->fecha->format('Y-m-d') : now()->format('Y-m-d')) }}"
                            required>
                    </div>
                </div>
                @if(isset($plan))
                    <div class="form-group">
                        <label class="form-inline">
                            <input type="checkbox" name="activo" value="1" {{ old('activo', $plan->activo) ? 'checked' : '' }}>
                            <span>Plan activo</span>
                        </label>
                    </div>
                @endif
            </div>
        </div>

        {{-- Dynamic Exercise Builder --}}
        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-header">
                <h3>🏋️ Ejercicios del Plan</h3>
                <button type="button" class="btn btn-sm btn-success" onclick="addExerciseRow()">+ Añadir Ejercicio</button>
            </div>

            <div class="exercise-labels">
                <span>Ejercicio</span>
                <span>Series</span>
                <span>Repeticiones</span>
                <span>Intensidad (RPE)</span>
                <span>KG</span>
                <span>Descanso</span>
                <span></span>
            </div>

            <div id="exercise-list">
                {{-- Existing exercises (for edit mode) --}}
                @if(isset($plan))
                    @foreach($plan->planEjercicios as $i => $pe)
                        <div class="exercise-row" data-index="{{ $i }}">
                            <select name="ejercicios[{{ $i }}][ejercicio_id]" class="form-control" required>
                                <option value="">— Ejercicio —</option>
                                @foreach($estimulos as $est)
                                    <optgroup label="{{ $est->nombre }}">
                                        @foreach($est->ejercicios as $ej)
                                            <option value="{{ $ej->id }}" {{ $pe->ejercicio_id == $ej->id ? 'selected' : '' }}>
                                                {{ $ej->nombre }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <input type="number" name="ejercicios[{{ $i }}][series]" class="form-control" value="{{ $pe->series }}"
                                min="1" placeholder="3" required>
                            <input type="text" name="ejercicios[{{ $i }}][repeticiones]" class="form-control"
                                value="{{ $pe->repeticiones }}" placeholder="10" required>
                            <input type="number" name="ejercicios[{{ $i }}][intensidad]" class="form-control"
                                value="{{ $pe->intensidad }}" min="0" max="10" placeholder="7" required>
                            <input type="number" name="ejercicios[{{ $i }}][kg]" class="form-control" value="{{ $pe->kg }}" min="0"
                                step="0.5" placeholder="—">
                            <input type="text" name="ejercicios[{{ $i }}][descanso]" class="form-control"
                                value="{{ $pe->descanso }}" placeholder="2-3'">
                            <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem">
            <a href="{{ route('admin.planes.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">{{ isset($plan) ? 'Guardar Cambios' : 'Crear Plan' }}</button>
        </div>
    </form>

    @push('scripts')
        <script>
            let exerciseIndex = {{ isset($plan) ? $plan->planEjercicios->count() : 0 }};
            const estimulos = @json($estimulos);

            function addExerciseRow() {
                const list = document.getElementById('exercise-list');
                const idx = exerciseIndex++;

                let options = '<option value="">— Ejercicio —</option>';
                estimulos.forEach(est => {
                    options += `<optgroup label="${est.nombre}">`;
                    est.ejercicios.forEach(ej => {
                        options += `<option value="${ej.id}">${ej.nombre}</option>`;
                    });
                    options += '</optgroup>';
                });

                const row = document.createElement('div');
                row.className = 'exercise-row';
                row.innerHTML = `
                    <select name="ejercicios[${idx}][ejercicio_id]" class="form-control" required>${options}</select>
                    <input type="number" name="ejercicios[${idx}][series]" class="form-control" value="2" min="1" placeholder="3" required>
                    <input type="text" name="ejercicios[${idx}][repeticiones]" class="form-control" value="10" placeholder="10" required>
                    <input type="number" name="ejercicios[${idx}][intensidad]" class="form-control" value="7" min="0" max="10" placeholder="7" required>
                    <input type="number" name="ejercicios[${idx}][kg]" class="form-control" min="0" step="0.5" placeholder="—">
                    <input type="text" name="ejercicios[${idx}][descanso]" class="form-control" placeholder="2-3'">
                    <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                `;
                list.appendChild(row);
            }

            function removeRow(btn) {
                btn.closest('.exercise-row').remove();
            }

            // Start with one empty row if creating new plan
            @unless(isset($plan))
                addExerciseRow();
            @endunless
        </script>
    @endpush
@endsection