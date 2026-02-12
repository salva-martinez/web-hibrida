@extends('layouts.app')
@section('title', 'Estímulos')

@section('content')
    <div class="page-header">
        <div>
            <h1>Estímulos</h1>
            <p class="subtitle">Grupos de ejercicios</p>
        </div>
        <a href="{{ route('admin.estimulos.create') }}" class="btn btn-primary">+ Nuevo Estímulo</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($estimulos->isEmpty())
                <div class="empty-state">
                    <div class="icon">⚡</div>
                    <h3>No hay estímulos</h3>
                    <p>Crea tu primer grupo de estímulos (ej: Básico, Auxiliar, Metabólico).</p>
                    <a href="{{ route('admin.estimulos.create') }}" class="btn btn-primary">+ Crear Estímulo</a>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Ejercicios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estimulos as $est)
                                <tr>
                                    <td>{{ $est->orden }}</td>
                                    <td><strong>{{ $est->nombre }}</strong></td>
                                    <td>{{ Str::limit($est->descripcion, 60) ?: '—' }}</td>
                                    <td><span class="badge badge-primary">{{ $est->ejercicios_count }}</span></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.estimulos.edit', $est) }}"
                                                class="btn btn-sm btn-primary">Editar</a>
                                            <form action="{{ route('admin.estimulos.destroy', $est) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar estímulo y sus ejercicios?')">
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