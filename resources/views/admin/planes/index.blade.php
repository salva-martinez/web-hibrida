@extends('layouts.app')
@section('title', 'Planes')

@section('content')
    <div class="page-header">
        <div>
            <h1>Planes de Ejercicios</h1>
            <p class="subtitle">Todas las rutinas pautadas</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($planes->isEmpty())
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <h3>No hay planes</h3>
                    <p>Crea una rutina de ejercicios para un paciente.</p>
                    <a href="{{ route('admin.planes.create') }}" class="btn btn-primary">+ Crear Plan</a>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Paciente</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Feedback</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planes as $plan)
                                <tr>
                                    <td><strong>{{ $plan->titulo }}</strong></td>
                                    <td>{{ $plan->paciente->nombre_completo }}</td>
                                    <td>{{ $plan->fecha->format('d/m/Y') }}</td>
                                    <td>
                                        @if($plan->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-warning">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->feedback)
                                            <span
                                                class="badge badge-{{ $plan->feedback->dureza > 7 ? 'danger' : ($plan->feedback->dureza > 4 ? 'warning' : 'success') }}">{{ $plan->feedback->dureza }}/10</span>
                                        @else
                                            <span style="color: var(--text-muted)">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.planes.show', $plan) }}"
                                                class="btn btn-sm btn-secondary">Ver</a>
                                            <a href="{{ route('admin.planes.edit', $plan) }}"
                                                class="btn btn-sm btn-primary">Editar</a>
                                            <form action="{{ route('admin.planes.destroy', $plan) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar plan?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        </div>
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