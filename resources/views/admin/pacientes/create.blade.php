@extends('layouts.app')
@section('title', 'Nuevo Paciente')

@section('content')
    <div class="page-header">
        <div>
            <h1>Nuevo Paciente</h1>
            <p class="subtitle">Dar de alta a un nuevo paciente</p>
        </div>
        <a href="{{ route('admin.pacientes.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('admin.pacientes.store') }}">
            @csrf
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre') }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="apellido1">Primer Apellido *</label>
                        <input type="text" name="apellido1" id="apellido1" class="form-control"
                            value="{{ old('apellido1') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido2">Segundo Apellido *</label>
                        <input type="text" name="apellido2" id="apellido2" class="form-control"
                            value="{{ old('apellido2') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email (opcional)</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"
                        placeholder="email@ejemplo.com">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Crear Paciente</button>
            </div>
        </form>
    </div>
@endsection