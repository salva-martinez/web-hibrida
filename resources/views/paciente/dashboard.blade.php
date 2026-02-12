@extends('layouts.app')
@section('title', 'Mi Perfil')

@section('content')
    <div class="page-header">
        <div>
            <h1>Hola, {{ auth()->user()->nombre }} 👋</h1>
            <p class="subtitle">Tu perfil de ejercicios</p>
        </div>
    </div>

    @if($planes->isEmpty())
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <h3>No tienes planes asignados</h3>
                    <p>Tu fisioterapeuta aún no te ha asignado ningún plan de ejercicios.</p>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h3>📋 Tus Planes de Ejercicios</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Feedback</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planes as $plan)
                                <tr>
                                    <td><strong>{{ $plan->titulo }}</strong></td>
                                    <td>{{ $plan->fecha->format('d/m/Y') }}</td>
                                    <td>
                                        @if($plan->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-warning">Anterior</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->feedback)
                                            <span class="badge badge-primary">✓ Enviado</span>
                                        @else
                                            <span style="color: var(--text-muted)">Pendiente</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('paciente.plan.show', $plan) }}" class="btn btn-sm btn-primary">Ver Plan
                                            →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection