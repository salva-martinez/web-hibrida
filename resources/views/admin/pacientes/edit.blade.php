@extends('layouts.app')
@section('title', 'Editar Paciente')

@section('content')
    <div class="page-header">
        <div>
            <h1>Editar Paciente</h1>
            <p class="subtitle">{{ $paciente->nombre_completo }}</p>
        </div>
        <a href="{{ route('admin.pacientes.index') }}" class="btn btn-secondary">← Volver</a>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('admin.pacientes.update', $paciente) }}">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control"
                            value="{{ old('nombre', $paciente->nombre) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido1">Primer Apellido *</label>
                        <input type="text" name="apellido1" id="apellido1" class="form-control"
                            value="{{ old('apellido1', $paciente->apellido1) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido2">Segundo Apellido *</label>
                        <input type="text" name="apellido2" id="apellido2" class="form-control"
                            value="{{ old('apellido2', $paciente->apellido2) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control"
                        value="{{ old('fecha_nacimiento', optional($paciente->fecha_nacimiento)->format('Y-m-d')) }}"
                        required>
                    <small>Se usará junto al nombre como verificación de identidad al iniciar sesión.</small>
                </div>
                <div class="form-group">
                    <label for="email">Email (opcional)</label>
                    <input type="email" name="email" id="email" class="form-control"
                        value="{{ old('email', $paciente->email) }}">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
@endsection