@extends('layouts.app')
@section('title', isset($plan) ? 'Editar Plan' : 'Nuevo Plan')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ isset($plan) ? 'Editar Plan' : (isset($clonedPlan) ? 'Duplicar Plan (Nuevo)' : 'Nuevo Plan de Ejercicios') }}</h1>
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
                                <option value="{{ $pac->id }}" {{ old('paciente_id', $plan->paciente_id ?? ($clonedPlan->paciente_id ?? request('paciente_id'))) == $pac->id ? 'selected' : '' }}>
                                    {{ $pac->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="titulo">Título del Plan *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control"
                            value="{{ old('titulo', $plan->titulo ?? ($clonedPlan ? $clonedPlan->titulo . ' (Copia)' : '')) }}" placeholder="Ej: Semana 1 - Plan Inicial"
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

        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-body">
                <div class="form-group mb-0">
                    <label for="num_dias">Días de entrenamiento por semana</label>
                    <select id="num_dias" class="form-control" style="max-width: 200px;" onchange="updateTabs()">
                        @php
                            // Al clonar un plan hay que preseleccionar tantos días como
                            // tenía el plan de origen: si no, el DOM solo se crea para
                            // el día 1 y los días 2..N se pierden en silencio al guardar.
                            $diasDelPlan = isset($plan)
                                ? $plan->planDias->count()
                                : (isset($clonedPlan) ? $clonedPlan->planDias->count() : 1);
                        @endphp
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ $diasDelPlan == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'Día' : 'Días' }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        {{-- Dynamic Exercise Builder --}}
        <div class="card" style="margin-bottom: 1.5rem">
            <div class="card-header border-b border-gray-200 bg-gray-50">
                <div class="flex space-x-2" id="tabs-container" style="display: flex; gap: 0.5rem; overflow-x: auto;">
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

    @push('scripts')
        <script>
            const estimulos = @json($estimulos);
            let exerciseIndexes = {}; // To keep track of exercise index per day
            let currentDay = 1;

            function updateTabs() {
                const numDias = parseInt(document.getElementById('num_dias').value);
                const tabsContainer = document.getElementById('tabs-container');
                const daysContainer = document.getElementById('days-container');

                // Preserve existing days data if possible
                for (let i = 1; i <= 7; i++) {
                    const tabBtn = document.getElementById(`tab-btn-${i}`);
                    const dayContent = document.getElementById(`day-content-${i}`);

                    if (i <= numDias) {
                        if (!tabBtn) {
                            // Create tab button
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.id = `tab-btn-${i}`;
                            btn.className = `btn btn-sm ${i === currentDay ? 'btn-primary' : 'btn-outline-primary'}`;
                            btn.textContent = `Día ${i}`;
                            btn.onclick = () => switchTab(i);
                            tabsContainer.appendChild(btn);

                            // Create day content
                            const content = document.createElement('div');
                            content.id = `day-content-${i}`;
                            content.style.display = i === currentDay ? 'block' : 'none';
                            
                            exerciseIndexes[i] = 0;

                            content.innerHTML = `
                                <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                    <h4 style="margin: 0;">Entrenamiento del Día ${i}</h4>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addExerciseRow(${i})">+ Añadir Ejercicio</button>
                                </div>
                                <div class="form-group">
                                    <label>Notas para el fisioterapeuta (opcional)</label>
                                    <input type="text" name="dias[${i}][notas_fisio]" class="form-control" placeholder="Ej: Énfasis en la técnica, día suave...">
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
                                <div id="exercise-list-${i}"></div>
                            `;
                            daysContainer.appendChild(content);

                            // Add one empty exercise row by default if creating new
                            @if(!isset($plan) && !isset($clonedPlan))
                                addExerciseRow(i);
                            @endif
                        }
                    } else {
                        // Remove tab and content if number of days is reduced
                        if (tabBtn) tabBtn.remove();
                        if (dayContent) dayContent.remove();
                    }
                }

                // Make sure currentDay is valid
                if (currentDay > numDias) {
                    switchTab(1);
                } else {
                    switchTab(currentDay);
                }
            }

            function switchTab(day) {
                currentDay = day;
                
                // Update buttons
                for (let i = 1; i <= 7; i++) {
                    const btn = document.getElementById(`tab-btn-${i}`);
                    if (btn) {
                        btn.className = `btn btn-sm ${i === day ? 'btn-primary' : 'btn-outline-primary'}`;
                    }
                }

                // Update contents
                for (let i = 1; i <= 7; i++) {
                    const content = document.getElementById(`day-content-${i}`);
                    if (content) {
                        content.style.display = i === day ? 'block' : 'none';
                    }
                }
            }

            function addExerciseRow(day, exerciseData = null) {
                const list = document.getElementById(`exercise-list-${day}`);
                if (!list) return;

                const idx = exerciseIndexes[day]++;

                // El DOM se construye con createElement + textContent/value (que NO
                // interpretan HTML). Usar innerHTML con datos de la base de datos
                // permitiría XSS almacenado (un nombre de ejercicio con comillas o
                // etiquetas se ejecutaría en el navegador de otro fisio).
                const row = document.createElement('div');
                row.className = 'exercise-row';

                const select = document.createElement('select');
                select.name = `dias[${day}][ejercicios][${idx}][ejercicio_id]`;
                select.className = 'form-control';
                select.required = true;

                const vacio = document.createElement('option');
                vacio.value = '';
                vacio.textContent = '— Ejercicio —';
                select.appendChild(vacio);

                estimulos.forEach(est => {
                    const group = document.createElement('optgroup');
                    group.label = est.nombre;

                    est.ejercicios.forEach(ej => {
                        const option = document.createElement('option');
                        option.value = ej.id;
                        option.textContent = ej.nombre;
                        if (exerciseData && exerciseData.ejercicio_id == ej.id) {
                            option.selected = true;
                        }
                        group.appendChild(option);
                    });

                    select.appendChild(group);
                });

                const crearCampo = (name, type, value, attrs = {}) => {
                    const input = document.createElement('input');
                    input.type = type;
                    input.name = name;
                    input.className = 'form-control';
                    input.value = value === null || value === undefined ? '' : value;
                    Object.entries(attrs).forEach(([key, val]) => input.setAttribute(key, val));
                    return input;
                };

                const prefijo = `dias[${day}][ejercicios][${idx}]`;

                row.appendChild(select);
                row.appendChild(crearCampo(`${prefijo}[series]`, 'number', exerciseData ? exerciseData.series : '2',
                    { min: '1', placeholder: '3', required: 'required' }));
                row.appendChild(crearCampo(`${prefijo}[repeticiones]`, 'text', exerciseData ? exerciseData.repeticiones : '10',
                    { placeholder: '10', required: 'required' }));
                row.appendChild(crearCampo(`${prefijo}[intensidad]`, 'number', exerciseData ? exerciseData.intensidad : '7',
                    { min: '0', max: '10', placeholder: '7', required: 'required' }));
                row.appendChild(crearCampo(`${prefijo}[kg]`, 'number', exerciseData ? (exerciseData.kg || '') : '',
                    { min: '0', step: 'any', placeholder: '—' }));
                row.appendChild(crearCampo(`${prefijo}[descanso]`, 'text', exerciseData ? (exerciseData.descanso || '') : '',
                    { placeholder: "2-3'" }));

                const btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'btn-remove';
                btnRemove.textContent = '✕';
                btnRemove.setAttribute('onclick', 'removeRow(this)');
                row.appendChild(btnRemove);

                list.appendChild(row);
            }

            function removeRow(btn) {
                btn.closest('.exercise-row').remove();
            }

            // Initialize on load
            document.addEventListener('DOMContentLoaded', function() {
                updateTabs();

                // Load existing data if editing or cloning
                @php
                    $planDiasToLoad = isset($plan) ? $plan->planDias : (isset($clonedPlan) ? $clonedPlan->planDias : collect());
                @endphp
                
                const planDias = @json($planDiasToLoad);
                
                if (planDias && planDias.length > 0) {
                    planDias.forEach(function(dia) {
                        const dayNum = dia.numero_dia;
                        
                        // Set notes if editing
                        const notesInput = document.querySelector(`input[name="dias[${dayNum}][notas_fisio]"]`);
                        if (notesInput && dia.notas_fisio) {
                            notesInput.value = dia.notas_fisio;
                        }

                        // We might need to remove the auto-added empty row if we have data to load
                        const list = document.getElementById(`exercise-list-${dayNum}`);
                        if (list) {
                            list.innerHTML = ''; // clear default row
                        }

                        if (dia.plan_ejercicios && dia.plan_ejercicios.length > 0) {
                            dia.plan_ejercicios.forEach(function(ej) {
                                addExerciseRow(dayNum, ej);
                            });
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection