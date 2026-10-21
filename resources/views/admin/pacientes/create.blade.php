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
                        @error('nombre')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="apellido1">Primer Apellido *</label>
                        <input type="text" name="apellido1" id="apellido1" class="form-control"
                            value="{{ old('apellido1') }}" required>
                        @error('apellido1')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="apellido2">Segundo Apellido *</label>
                        <input type="text" name="apellido2" id="apellido2" class="form-control"
                            value="{{ old('apellido2') }}" required>
                        @error('apellido2')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control"
                        value="{{ old('fecha_nacimiento') }}" required>
                    @error('fecha_nacimiento')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <small>Se usará junto al nombre como verificación de identidad al iniciar sesión.</small>
                </div>
                <div class="form-group">
                    <label for="email">Email (opcional)</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"
                        placeholder="email@ejemplo.com">
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Crear Paciente</button>
            </div>
        </form>
    </div>
@endsection