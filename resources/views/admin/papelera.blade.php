@extends('layouts.app')
@section('title', 'Papelera')

@section('content')
    <div class="page-header">
        <div>
            <h1>🗑️ Papelera</h1>
            <p style="color: var(--text-secondary); margin-top: .25rem">
                Nada se borra de forma definitiva. Aquí puedes recuperar los pacientes, planes y
                ejercicios que has desactivado, con todo su historial clínico intacto.
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header">👥 Pacientes desactivados <span class="badge badge-primary">{{ $pacientes->count() }}</span></div>
        <div class="card-body">
            @if($pacientes->isEmpty())
                <div class="empty-state">
                    <p>No hay pacientes desactivados.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Email</th>
                                <th>Desactivado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pacientes as $paciente)
                                <tr>
                                    <td><strong>{{ $paciente->nombre_completo }}</strong></td>
                                    <td>{{ $paciente->email ?? '—' }}</td>
                                    <td>{{ optional($paciente->deleted_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.papelera.paciente.restore', $paciente->id) }}"
                                            class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">♻️ Restaurar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header">📋 Planes archivados <span class="badge badge-primary">{{ $planes->count() }}</span></div>
        <div class="card-body">
            @if($planes->isEmpty())
                <div class="empty-state">
                    <p>No hay planes archivados.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Paciente</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planes as $plan)
                                <tr>
                                    <td><strong>{{ $plan->titulo }}</strong></td>
                                    <td>{{ $plan->paciente->nombre_completo ?? '—' }}</td>
                                    <td>{{ $plan->fecha->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge badge-{{ $plan->activo ? 'success' : 'warning' }}">
                                            {{ $plan->activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.papelera.plan.restore', $plan->id) }}"
                                            class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">♻️ Restaurar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem">
        <div class="card-header">🧩 Estímulos desactivados <span class="badge badge-primary">{{ $estimulos->count() }}</span></div>
        <div class="card-body">
            @if($estimulos->isEmpty())
                <div class="empty-state">
                    <p>No hay estímulos desactivados.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Estímulo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estimulos as $estimulo)
                                <tr>
                                    <td>{{ $estimulo->orden }}</td>
                                    <td><strong>{{ $estimulo->nombre }}</strong></td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.papelera.estimulo.restore', $estimulo->id) }}"
                                            class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">♻️ Restaurar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">🏋️ Ejercicios desactivados <span class="badge badge-primary">{{ $ejercicios->count() }}</span></div>
        <div class="card-body">
            @if($ejercicios->isEmpty())
                <div class="empty-state">
                    <p>No hay ejercicios desactivados.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Ejercicio</th>
                                <th>Estímulo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ejercicios as $ejercicio)
                                <tr>
                                    <td><strong>{{ $ejercicio->nombre }}</strong></td>
                                    <td>{{ $ejercicio->estimulo->nombre ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.papelera.ejercicio.restore', $ejercicio->id) }}"
                                            class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">♻️ Restaurar</button>
                                        </form>
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
