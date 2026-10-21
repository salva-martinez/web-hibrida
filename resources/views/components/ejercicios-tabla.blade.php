{{--
    Tabla de ejercicios de un día del plan.

    Es la misma tabla en el panel del fisioterapeuta y en el del paciente: lo
    único que cambia es cómo se abre el vídeo (pestaña nueva en el panel,
    modal en el del paciente).

    @param \App\Models\PlanDia $dia
    @param bool $modal  Si true, el enlace del vídeo lo abre el modal.
--}}
@props(['dia', 'modal' => false])

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
            <tr>
                <td class="estimulo-header {{ \App\Models\PlanDia::claseCssEstimulo($estimuloNombre) }}"
                    rowspan="{{ $ejercicios->count() + 1 }}">
                    {{ $estimuloNombre }}
                </td>
            </tr>
            @foreach($ejercicios as $pe)
                <tr>
                    <td class="ejercicio-nombre">
                        @if($pe->ejercicio->tieneVideo())
                            @if($modal)
                                <a href="javascript:void(0)" class="video-link"
                                    data-video-url="{{ $pe->ejercicio->embed_url }}"
                                    data-video-title="{{ $pe->ejercicio->nombre }}">
                                    <span>▶️</span> {{ $pe->ejercicio->nombre }}
                                </a>
                            @else
                                <a href="{{ $pe->ejercicio->youtube_url }}" target="_blank" rel="noopener noreferrer" class="video-link"
                                    style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    <span>▶️</span> {{ $pe->ejercicio->nombre }}
                                </a>
                            @endif
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
