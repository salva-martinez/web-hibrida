@extends('layouts.app')
@section('title', 'Pacientes')

@section('content')
    <div class="page-header">
        <div>
            <h1>Pacientes</h1>
            <p class="subtitle">Gestiona tus pacientes</p>
        </div>
        <a href="{{ route('admin.pacientes.create') }}" class="btn btn-primary">+ Nuevo Paciente</a>
    </div>

    <div class="search-container" style="margin-bottom: 1.5rem;">
        <form action="{{ route('admin.pacientes.index') }}" method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" class="form-control" placeholder="🔍 Buscar por nombre..."
                value="{{ request('search') }}" style="max-width: 300px;">
            <button type="submit" class="btn btn-secondary">Buscar</button>
            @if(request('search'))
                <a href="{{ route('admin.pacientes.index') }}" class="btn btn-link" style="align-self: center;">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            @if($pacientes->isEmpty())
                <div class="empty-state">
                    <div class="icon">🏃</div>
                    <h3>No hay pacientes</h3>
                    <p>Crea tu primer paciente para empezar a pautar ejercicios.</p>
                    <a href="{{ route('admin.pacientes.create') }}" class="btn btn-primary">+ Crear Paciente</a>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Planes</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pacientes as $pac)
                                <tr>
                                    <td><strong>{{ $pac->nombre_completo }}</strong></td>
                                    <td>{{ $pac->email ?? '—' }}</td>
                                    <td><span class="badge badge-primary">{{ $pac->planesComoPaciente->count() }}</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.pacientes.show', $pac) }}"
                                                class="btn btn-sm btn-secondary">Ver</a>
                                            <a href="{{ route('admin.planes.create', ['paciente_id' => $pac->id]) }}"
                                                class="btn btn-sm btn-success">Añadir Plan</a>
                                            <a href="{{ route('admin.pacientes.edit', $pac) }}"
                                                class="btn btn-sm btn-primary">Editar</a>
                                            <form action="{{ route('admin.pacientes.destroy', $pac) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar paciente?')">
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