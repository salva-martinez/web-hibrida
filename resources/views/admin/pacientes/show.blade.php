@extends('layouts.app')
@section('title', $paciente->nombre_completo)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $paciente->nombre_completo }}</h1>
            <p class="subtitle">Ficha del paciente</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.planes.create', ['paciente_id' => $paciente->id]) }}" class="btn btn-success">+ Añadir
                Plan</a>
            <a href="{{ route('admin.pacientes.edit', $paciente) }}" class="btn btn-primary">Editar</a>
            <a href="{{ route('admin.pacientes.index') }}" class="btn btn-secondary">← Volver</a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header">
            <h3>📋 Planes de Ejercicios</h3>
        </div>
        <div class="card-body">
            @if($paciente->planesComoPaciente->isEmpty())
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <h3>Sin planes asignados</h3>
                    <p>Crea un plan de ejercicios para este paciente.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Feedback</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paciente->planesComoPaciente as $plan)
                                <tr>
                                    <td><strong>{{ $plan->titulo }}</strong></td>
                                    <td>{{ $plan->fecha->format('d/m/Y') }}</td>
                                    <td>
                                        @if($plan->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-warning">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $diasConFeedback = $plan->planDias->filter->feedback; @endphp
                                        @if($diasConFeedback->isEmpty())
                                            <span style="color: var(--text-muted)">—</span>
                                        @else
                                            @foreach($diasConFeedback as $dia)
                                                <div style="margin-bottom: 4px">
                                                    <span
                                                        class="badge badge-{{ $dia->feedback->dureza > 7 ? 'danger' : ($dia->feedback->dureza > 4 ? 'warning' : 'success') }}">
                                                        Día {{ $dia->numero_dia }} — Dureza: {{ $dia->feedback->dureza }}/10
                                                    </span>
                                                    @if($dia->feedback->comentario)
                                                        <br><small style="color: var(--text-secondary)">
                                                            {{ Str::limit($dia->feedback->comentario, 40) }}</small>
                                                    @endif
                                                    @if($dia->feedback->analisis_ia)
                                                        <br><span class="badge badge-info" title="Análisis IA disponible">IA ✨</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.planes.show', $plan) }}" class="btn btn-sm btn-secondary">Ver
                                            Plan</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection