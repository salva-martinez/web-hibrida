/**
 * Constructor de planes (días de entrenamiento y ejercicios).
 *
 * Sustituye al JavaScript que estaba embebido en la vista. Los datos del
 * servidor (catálogo de estímulos y días a cargar) llegan en un bloque
 * <script type="application/json">, y la configuración de la página en
 * atributos data-*.
 *
 * Nota de seguridad: el DOM se construye con createElement + textContent/value,
 * que NO interpretan HTML. Usar innerHTML con datos de la base de datos
 * permitiría XSS almacenado.
 */
export function initPlanEditor() {
    const contenedor = document.querySelector('[data-plan-editor]');
    if (!contenedor) {
        return;
    }

    // Los datos llegan en un atributo data- de la propia vista (sin <script> inline).
    const datos = JSON.parse(contenedor.dataset.datos || '{}');
    const estimulos = datos.estimulos;
    const diasACargar = datos.dias;
    const diasConFeedback = datos.diasConFeedback;
    const anadirFilaVacia = datos.anadirFilaVacia;

    const selectNumDias = document.getElementById('num_dias');
    const tabsContainer = document.getElementById('tabs-container');
    const daysContainer = document.getElementById('days-container');

    /** Índice de ejercicio por día, para generar los names del formulario. */
    const exerciseIndexes = {};
    let currentDay = 1;
    let numDiasPrevio = null;

    // -----------------------------------------------------------------
    // Cambio del número de días
    // -----------------------------------------------------------------
    function onChangeNumDias() {
        const nuevo = parseInt(selectNumDias.value, 10);

        // Reducir los días borra los que desaparezcan, y con ellos el
        // feedback del paciente: conviene avisar antes.
        if (numDiasPrevio !== null && nuevo < numDiasPrevio && diasConFeedback > 0) {
            const acepta = window.confirm(
                'Al reducir los días desaparecen del plan. Si alguno de los días que ' +
                'eliminas tiene feedback del paciente, ese feedback se perderá.\n\n' +
                '¿Quieres continuar?'
            );

            if (!acepta) {
                selectNumDias.value = numDiasPrevio;
                return;
            }
        }

        numDiasPrevio = nuevo;
        updateTabs();
    }

    selectNumDias.addEventListener('change', onChangeNumDias);

    // -----------------------------------------------------------------
    // Pestañas de días
    // -----------------------------------------------------------------
    function updateTabs() {
        const numDias = parseInt(selectNumDias.value, 10);

        for (let i = 1; i <= 7; i++) {
            const tabBtn = document.getElementById(`tab-btn-${i}`);
            const dayContent = document.getElementById(`day-content-${i}`);

            if (i <= numDias) {
                if (!tabBtn) {
                    crearDia(i);
                }
            } else {
                // El día desaparece del formulario: al guardar, el backend lo
                // eliminará del plan (y su feedback con él).
                if (tabBtn) tabBtn.remove();
                if (dayContent) dayContent.remove();
            }
        }

        switchTab(currentDay > numDias ? 1 : currentDay);
    }

    function crearDia(i) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.id = `tab-btn-${i}`;
        btn.className = `btn btn-sm ${i === currentDay ? 'btn-primary' : 'btn-outline-primary'}`;
        btn.textContent = `Día ${i}`;
        btn.addEventListener('click', () => switchTab(i));
        tabsContainer.appendChild(btn);

        const content = document.createElement('div');
        content.id = `day-content-${i}`;
        content.style.display = i === currentDay ? 'block' : 'none';

        exerciseIndexes[i] = 0;

        // Plantilla estática: no contiene datos del usuario.
        content.innerHTML = `
            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0;">Entrenamiento del Día ${i}</h4>
                <button type="button" class="btn btn-sm btn-success" data-add-exercise="${i}">+ Añadir Ejercicio</button>
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

        content.querySelector('[data-add-exercise]').addEventListener('click', () => addExerciseRow(i));
        daysContainer.appendChild(content);

        // Al crear un plan de cero se deja una fila lista para rellenar.
        if (anadirFilaVacia) {
            addExerciseRow(i);
        }
    }

    function switchTab(day) {
        currentDay = day;

        for (let i = 1; i <= 7; i++) {
            const btn = document.getElementById(`tab-btn-${i}`);
            if (btn) {
                btn.className = `btn btn-sm ${i === day ? 'btn-primary' : 'btn-outline-primary'}`;
            }

            const content = document.getElementById(`day-content-${i}`);
            if (content) {
                content.style.display = i === day ? 'block' : 'none';
            }
        }
    }

    // -----------------------------------------------------------------
    // Filas de ejercicio
    // -----------------------------------------------------------------
    function addExerciseRow(day, exerciseData = null) {
        const list = document.getElementById(`exercise-list-${day}`);
        if (!list) {
            return;
        }

        const idx = exerciseIndexes[day]++;

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

        estimulos.forEach((est) => {
            const group = document.createElement('optgroup');
            group.label = est.nombre;

            est.ejercicios.forEach((ej) => {
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
        btnRemove.addEventListener('click', () => removeRow(btnRemove));
        row.appendChild(btnRemove);

        list.appendChild(row);
    }

    function removeRow(btn) {
        btn.closest('.exercise-row').remove();
    }

    // -----------------------------------------------------------------
    // Carga inicial
    // -----------------------------------------------------------------
    numDiasPrevio = parseInt(selectNumDias.value, 10);
    updateTabs();

    if (diasACargar && Object.keys(diasACargar).length > 0) {
        Object.values(diasACargar).forEach((dia) => {
            const dayNum = dia.numero_dia;

            const notesInput = document.querySelector(`input[name="dias[${dayNum}][notas_fisio]"]`);
            if (notesInput && dia.notas_fisio) {
                notesInput.value = dia.notas_fisio;
            }

            const list = document.getElementById(`exercise-list-${dayNum}`);
            if (list) {
                list.innerHTML = '';
            }

            if (dia.plan_ejercicios && dia.plan_ejercicios.length > 0) {
                dia.plan_ejercicios.forEach((ej) => addExerciseRow(dayNum, ej));
            }
        });
    }
}
