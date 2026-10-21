@extends('layouts.app')
@section('title', isset($plan) ? 'Editar Plan' : 'Nuevo Plan')

@section('content')
    @php
        // Días del plan actual que ya tienen feedback del paciente: si
        // desaparecen del formulario, ese feedback se borra.
        $diasConFeedback = isset($plan)
            ? $plan->planDias->filter(fn ($dia) => $dia->feedback !== null)->count()
            : 0;

        // Días a precargar en el editor.
        // Si la validación falló se repone lo que el usuario ya había escrito
        // (si no, se pierde todo el trabajo); si no, se carga el plan existente
        // o el plan clonado.
        $diasRepuestos = old('dias');
        $diasACargar = [];

        if (is_array($diasRepuestos) && count($diasRepuestos) > 0) {
            // Formato del formulario (tras un error de validación)
            foreach ($diasRepuestos as $numDia => $dia) {
                $diasACargar[(int) $numDia] = [
                    'numero_dia' => (int) $numDia,
                    'notas_fisio' => $dia['notas_fisio'] ?? null,
                    'plan_ejercicios' => array_values(array_map(function ($ej) {
                        return [
                            'ejercicio_id' => $ej['ejercicio_id'] ?? null,
                            'series' => $ej['series'] ?? null,
                            'repeticiones' => $ej['repeticiones'] ?? null,
                            'intensidad' => $ej['intensidad'] ?? null,
                            'kg' => $ej['kg'] ?? null,
                            'descanso' => $ej['descanso'] ?? null,
                        ];
                    }, $dia['ejercicios'] ?? [])),
                ];
            }
        } else {
            // Plan existente o plan clonado
            $planDiasToLoad = isset($plan)
                ? $plan->planDias
                : (isset($clonedPlan) ? $clonedPlan->planDias : collect());

            foreach ($planDiasToLoad as $dia) {
                $diasACargar[$dia->numero_dia] = [
                    'numero_dia' => $dia->numero_dia,
                    'notas_fisio' => $dia->notas_fisio,
                    'plan_ejercicios' => $dia->planEjercicios->map(function ($pe) {
                        return [
                            'ejercicio_id' => $pe->ejercicio_id,
                            'series' => $pe->series,
                            'repeticiones' => $pe->repeticiones,
                            'intensidad' => $pe->intensidad,
                            'kg' => $pe->kg,
                            'descanso' => $pe->descanso,
                        ];
                    })->values()->all(),
                ];
            }
        }
    @endphp
    {{-- Datos que consume el módulo resources/js/plan-editor.js --}}
    @php
        $datosEditor = [
            'estimulos' => $estimulos,
            'dias' => $diasACargar,
            'diasConFeedback' => $diasConFeedback,
            'anadirFilaVacia' => ! isset($plan) && ! isset($clonedPlan),
        ];
    @endphp
    <script type="application/json" id="plan-editor-data">@json($datosEditor)</script>

    <div class="page-header">
        <div>
            <h1>{{ isset($plan) ? 'Editar Plan' : (isset($clonedPlan) ? 'Duplicar Plan (Nuevo)' : 'Nuevo Plan de Ejercicios') }}</h1>
        </div>
        <a href="{{ route('admin.planes.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <form method="POST" action="{{ isset($plan) ? route('admin.planes.update', $plan) : route('admin.planes.store') }}"
        id="planForm" data-plan-editor>
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
                                <option value="{{ $pac->id }}" {{ old('paciente_id', $plan->paciente_id ?? ($clonedPlan->paciente_id ?? request('paciente_id'))) == $pac->id ? 'selected' : '' }}>
                                    {{ $pac->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                        @error('paciente_id')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="titulo">Título del Plan *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control"
                            value="{{ old('titulo', $plan->titulo ?? ($clonedPlan ? $clonedPlan->titulo . ' (Copia)' : '')) }}" placeholder="Ej: Semana 1 - Plan Inicial"
                            required>
                        @error('titulo')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha *</label>
                        <input type="date" name="fecha" id="fecha" class="form-control"
                            value="{{ old('fecha', isset($plan) ? $plan->fecha->format('Y-m-d') : now()->format('Y-m-d')) }}"
                            required>
                        @error('fecha')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-error" style="margin-top: 1rem">
                        <span>⚠️</span>
                        <ul style="margin: .25rem 0 0 1rem">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
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

        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-body">
                <div class="form-group" style="margin-bottom: 0">
                    <label for="num_dias">Días de entrenamiento por semana</label>
                    <select id="num_dias" class="form-control" style="max-width: 200px;" data-num-dias>
                        @php
                            // Al clonar un plan hay que preseleccionar tantos días como
                            // tenía el plan de origen: si no, el DOM solo se crea para
                            // el día 1 y los días 2..N se pierden en silencio al guardar.
                            // Si la validación falló, manda lo que el usuario escribió.
                            $diasRepuestos = old('dias');
                            $diasDelPlan = is_array($diasRepuestos) && count($diasRepuestos) > 0
                                ? count($diasRepuestos)
                                : (isset($plan)
                                    ? $plan->planDias->count()
                                    : (isset($clonedPlan) ? $clonedPlan->planDias->count() : 1));
                        @endphp
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ $diasDelPlan == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'Día' : 'Días' }}</option>
                        @endfor
                    </select>

                    @if($diasConFeedback > 0)
                        <p style="font-size: .8rem; color: var(--text-muted); margin-top: .5rem">
                            ⚠️ Este plan tiene feedback registrado en {{ $diasConFeedback }}
                            {{ $diasConFeedback === 1 ? 'día' : 'días' }}. Si reduces el número de días,
                            el feedback de los días eliminados se perderá.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Dynamic Exercise Builder --}}
        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-header">
                <div id="tabs-container" style="display: flex; gap: 0.5rem; overflow-x: auto;">
                    <!-- Tabs will be generated here -->
                </div>
            </div>

            <div id="days-container" class="card-body">
                <!-- Day contents will be generated here -->
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem">
            <a href="{{ route('admin.planes.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">{{ isset($plan) ? 'Guardar Cambios' : 'Crear Plan' }}</button>
        </div>
    </form>

    
@endsection