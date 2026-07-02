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

            <div class="login-tabs">
                <button class="login-tab active" onclick="switchTab('fisio')">🩺 Fisioterapeuta</button>
                <button class="login-tab" onclick="switchTab('paciente')">🏃 Paciente</button>
            </div>

            {{-- Fisio Login --}}
            <form class="login-form active" id="form-fisio" method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="login_type" value="fisio">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="fisio@fisioapp.com"
                        value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                        required>
                </div>

                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </form>

            {{-- Paciente Login --}}
            <form class="login-form" id="form-paciente" method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="login_type" value="paciente">

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tu nombre"
                        value="{{ old('nombre') }}" required>
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
        <script>
            function switchTab(type) {
                document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.login-form').forEach(f => f.classList.remove('active'));

                event.target.classList.add('active');
                document.getElementById('form-' + type).classList.add('active');
            }
        </script>
    @endpush
@endsection