@extends('layouts.app')
@section('title', 'Iniciar Sesión')

@section('content')
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <span>💪</span>
                <h1>FisioApp</h1>
                <p>Gestión de pautas de rehabilitación</p>
            </div>

            @php
                // Si el login falla se repone la pestaña que el usuario estaba usando.
                $pestanaActiva = old('login_type', 'fisio');
            @endphp

            <div class="login-tabs" id="login-tabs">
                <button type="button" class="login-tab {{ $pestanaActiva === 'fisio' ? 'active' : '' }}"
                    data-login-tab="fisio">🩺 Fisioterapeuta</button>
                <button type="button" class="login-tab {{ $pestanaActiva === 'paciente' ? 'active' : '' }}"
                    data-login-tab="paciente">🏃 Paciente</button>
            </div>

            {{-- Fisio Login --}}
            <form class="login-form {{ $pestanaActiva === 'fisio' ? 'active' : '' }}" id="form-fisio" method="POST"
                action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="login_type" value="fisio">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="fisio@fisioapp.com"
                        value="{{ old('email') }}" required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                        required>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>

                <p style="text-align:center; margin-top: 1rem; font-size: .85rem; color: var(--text-secondary)">
                    ¿Eres fisioterapeuta y aún no tienes cuenta?
                    <a href="{{ route('register') }}" style="color: var(--primary);">Regístrate gratis</a>
                </p>
            </form>

            {{-- Paciente Login --}}
            <form class="login-form {{ $pestanaActiva === 'paciente' ? 'active' : '' }}" id="form-paciente" method="POST"
                action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="login_type" value="paciente">

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tu nombre"
                        value="{{ old('nombre') }}" required>
                    @error('nombre')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="apellido1">Primer Apellido</label>
                    <input type="text" name="apellido1" id="apellido1" class="form-control" placeholder="Primer apellido"
                        value="{{ old('apellido1') }}" required>
                </div>

                <div class="form-group">
                    <label for="apellido2">Segundo Apellido</label>
                    <input type="text" name="apellido2" id="apellido2" class="form-control" placeholder="Segundo apellido"
                        value="{{ old('apellido2') }}" required>
                </div>

                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control"
                        value="{{ old('fecha_nacimiento') }}" required>
                </div>

                <button type="submit" class="btn btn-primary">Acceder a mi plan</button>
            </form>
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection
