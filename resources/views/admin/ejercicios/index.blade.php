@extends('layouts.app')
@section('title', 'Ejercicios')

@section('content')
    <div class="page-header">
        <div>
            <h1>Ejercicios</h1>
            <p class="subtitle">Biblioteca de ejercicios con vídeos</p>
        </div>
        <a href="{{ route('admin.ejercicios.create') }}" class="btn btn-primary">+ Nuevo Ejercicio</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($ejercicios->isEmpty())
                <div class="empty-state">
                    <div class="icon">🏋️</div>
                    <h3>No hay ejercicios</h3>
                    <p>Crea ejercicios y asócialos a un estímulo.</p>
                    <a href="{{ route('admin.ejercicios.create') }}" class="btn btn-primary">+ Crear Ejercicio</a>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Estímulo</th>
                                <th>Vídeo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ejercicios as $ej)
                                <tr>
                                    <td><strong>{{ $ej->nombre }}</strong></td>
                                    <td><span class="badge badge-primary">{{ $ej->estimulo->nombre }}</span></td>
                                    <td>
                                        @if($ej->video_url)
                                            <a href="{{ $ej->video_url }}" target="_blank" class="btn btn-sm btn-secondary">▶ Ver
                                                vídeo</a>
                                        @else
                                            <span style="color: var(--text-muted)">Sin vídeo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.ejercicios.edit', $ej) }}"
                                                class="btn btn-sm btn-primary">Editar</a>
                                            <form action="{{ route('admin.ejercicios.destroy', $ej) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar ejercicio?')">
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